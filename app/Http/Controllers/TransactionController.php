<?php

namespace App\Http\Controllers;

use App\Transaction;
use App\PendingTransaction;
use App\Mail\Invoice;
use App\Mail\PendingInvoice;
use App\Mail\Receipt;
use App\Rules\ValidETHAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'from_id' => 'required_without:from_email|exists:users,id',
            'from_name' => 'required_without:from_id|string',
            'from_email' => 'required_without:from_id|email',
            'to_address' => ['required', new ValidETHAddress],
            'value' => 'required|string|numeric|min:0.01',
            'receipt_list' => 'nullable|string|json',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->errors()->all(),
            ], 400);
        }

        $to = Auth::user();
        $from = $request->from_id ? User::find($request->from_id) : null;

        if (!$to->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'errors' => [ 'You have not verified your email address.' ],
            ], 403);
        }
        if ($from && (!$from->hasVerifiedEmail() || !$from->verified)) {
            return response()->json([
                'success' => false,
                'errors' => [ 'The other party has not verified their email address and identity.' ],
            ], 403);
        }
        if (!in_array($request->to_address, Auth::user()->eth)) {
            return response()->json([
                'success' => false,
                'errors' => [ 'The ETH address this order is being sent from is not marked ' .
                    'as one belonging to this account. Please add it by going to "Edit Profile"' .
                    'before continuing.' ],
            ], 403);
        }
        if (round($request->value, 2) != $request->value) {
            return response()->json([
                'success' => false,
                'errors' => [ 'Total value must not have more than two decimal places' ],
            ], 400);
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => config('app.api'),
            'timeout' => 3.0,
        ]);
        try {
            $receipt = json_decode($request->receipt_list, false, 512, JSON_THROW_ON_ERROR);
            if (sizeof($receipt) < 1) {
                return response()->json([
                    'success' => false,
                    'errors' => [ 'receipt_list cannot be empty' ],
                ], 400);
            }
            $tot = '0.00';
            foreach ($receipt as $item) {
                if (!isset($item->name) || !isset($item->price)) {
                    return response()->json([
                        'success' => false,
                        'errors' => [
                            'receipt_list must have a name and price for every item'
                        ],
                    ], 400);
                } else if (!is_numeric($item->price) || $item->price < 0.01) {
                    return response()->json([
                        'success' => false,
                        'errors' => [ 'receipt_list prices must all be numeric and >= 0.01' ],
                    ], 400);
                } else if (round($item->price, 2) != $item->price) {
                    return response()->json([
                        'success' => false,
                        'errors' => [ 'receipt_list prices cannot have more than two decimal places' ],
                    ], 400);
                } else {
                    $tot = bcadd($tot, $item->price, 2);
                }
            }
            if (bccomp($tot, $request->value, 2) !== 0) {
                return response()->json([
                    'success' => false,
                    'errors' => [ 'receipt_list prices don\'t add up to a value of '. $request->value .' SAGA' ],
                ], 400);
            }
            if ($from) {
                $response = $client->request('POST', 'payments', [
                    \GuzzleHttp\RequestOptions::JSON => [
                        'customer_name' => $from->getFullName(),
                        'customer_email' => $from->email,
                        'customer_address' => $from->eth[0],
                        'merchant_name' => $to->getFullName(),
                        'merchant_address' => $request->to_address,
                        'amount' => round($request->value, 2),
                        'callback_api' => route('payments.update'),
                    ], 'headers' => [
                        'Authorization' => 'Bearer ' . Cache::get('jwt_token'),
                    ]
                ]);
                $json = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
                return response()->json($json);
                if (isset($json->status) && $json->status === 'success') {
                    $order = Transaction::create([
                        'id' => $json->data->id,
                        'to_id' => $to->id,
                        'from_id' => $from->id,
                        'from_name' => $from->getFullName(),
                        'from_email' => $from->email,
                        'from_address' => $from->eth[0],
                        'to_address' => $request->to_address,
                        // Isn't $request->value, but they have to be the same numeric value.
                        // I chose $tot here so that extra trailing zeros don't get included.
                        'value' => $tot,
                        'receipt_list' => $receipt,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'errors' => [ $json->message ?? 'Unknown backend error' ],
                    ], 500);
                }
                // TODO: tokenize url
                Mail::to($from->email)->queue(new Invoice($order));
            } else {
                $order = PendingTransaction::create([
                    'to_id' => $to->id,
                    'to_address' => $request->to_address,
                    'from_name' => $request->from_name,
                    'from_email' => $request->from_email,
                    'value' => $tot,
                    'receipt_list' => $receipt,
                ]);
                // TODO: tokenize url
                Mail::to($request->from_email)->queue(new PendingInvoice($order));
            }
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'to_address' => $request->to_address,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [ $e->getMessage() ],
            ], 500);
        }
    }

    /**
     * Show the form for a guest customer to enter their ETH address.
     *
     * @param   \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function showCreateGuest(Request $request, $id)
    {
        $pt = PendingTransaction::findOrFail($id);
        return view('createguest', compact('pt'));
    }

    /**
     * Add the ETH address a guest customer will pay from.
     *
     * @param   \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function createGuest(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'from_address' => ['required', new ValidETHAddress],
        ]);

        $pt = PendingTransaction::findOrFail($id);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->errors()->all(),
            ], 400);
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => config('app.api'),
            'timeout' => 3.0,
        ]);
        $response = $client->request('POST', 'payments', [
            \GuzzleHttp\RequestOptions::JSON => [
                'customer_name' => $pt->from_name,
                'customer_address' => $request->from_address,
                'customer_email' => $pt->from_email,
                'merchant_name' => $pt->merchant->getFullName(),
                'merchant_address' => $pt->to_address,
                'amount' => round($pt->value, 2),
                'callback_api' => route('payments.update'),
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . Cache::get('jwt_token'),
            ],
        ]);
        try {
            $json = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'successs' => false,
                'errors' => [ $e->getMessage() ],
            ], 500);
        }

        if (isset($json->status) && $json->status === 'success') {
            $transaction = Transaction::create([
                'id' => $json->data->id,
                'to_id' => $pt->to_id,
                'from_name' => $pt->from_name,
                'from_email' => $pt->from_email,
                'from_address' => $request->from_address,
                'to_address' => $pt->to_address,
                'value' => $pt->value,
                'receipt_list' => $pt->receipt_list,
            ]);
            if ($transaction) {
                $pt->delete();
            }
            session([
                'customer_name' => null,
                'customer_email' => null,
                'amount' => null,
            ]);
            return response()->json([
                'success' => true
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'errors' => [ $json->message ?? 'Unknown backend error' ],
            ], 500);
        }
    }

    /**
     * List the orders that a user has sent.
     *
     * @param   \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function showOrders(Request $request)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('app.api'),
            'timeout' => 3.0,
        ]);
        $orders = collect();
        $order_ids = Auth::user()->outgoingTransactions->pluck('id');
        try {
            foreach ($order_ids as $id) {
                $response = $client->request('GET', "payments/{$id}", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . Cache::get('jwt_token'),
                    ],
                ]);
                $json = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                if (isset($json['status']) && $json['status'] === 'success') {
                    $t = Transaction::findOrFail($json['data']['id']);
                    $orders->push(
                        \array_merge(
                            \array_except($json['data'], 'callback_url'),
                            ['receipt_list' => $t->receipt_list]
                        )
                    );
                } else {
                    return response()->json([
                        'success' => false,
                        'errors' => [ $json['message'] ?? 'Unknown backend error' ],
                    ]);
                }
            }
            return response()->json([
                'success' => true,
                'orders' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [ $e->getMessage() ],
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            $json = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Malformed JSON',
            ], 400);
        }
        $validate = Validator::make($json, [
            'tx_hash' => 'required|size:64',
            'id' => 'exists:transactions',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors()->first(),
            ], 400);
        }

        $order = Transaction::find($json['id']);
        $order->tx_hash = $request->tx_hash;
        $order->save();

        Mail::to($order->from_email)
            ->cc($order->merchant)
            ->queue(new Receipt($order));

        return response()->json([
            'status' => 'success',
            'data' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}

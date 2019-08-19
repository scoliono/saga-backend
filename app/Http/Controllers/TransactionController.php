<?php

namespace App\Http\Controllers;

use App\Transaction;
use App\Mail\Invoice;
use App\Rules\ValidETHAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'to_name' => 'required|string',
            'to_email' => 'required|email',
            'from_address' => ['required', new ValidETHAddress],
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
        if (!Auth::user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'errors' => [ 'You have not verified your email address.' ],
            ], 403);
        }
        if (!in_array($request->from_address, Auth::user()->eth())) {
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
        $from = Auth::user();
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
            $response = $client->request('POST', 'payments', [
                'form_params' => [
                    'name' => $from->getFullName(),
                    'email' => $from->email,
                    'from_address' => $request->from_address,
                    'to_address' => $request->to_address,
                    'value' => round($request->value, 2),
                    'receipt_list' => $request->receipt_list,
                    'callback_api' => route('payments.update', ['']),
                ]
            ]);
            $response = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
            $order = Transaction::create([
                'id' => $response->id,
                'to_name' => $request->to_name,
                'to_email' => $request->to_email,
                'from_id' => $from->id,
                'from_address' => $request->from_address,
                'to_address' => $request->to_address,
                // Isn't $request->value, but they have to be the same numeric value.
                // I chose $tot here so that extra trailing zeros don't get included.
                'value' => $tot,
                'receipt_list' => $receipt,
            ]);
            Mail::to($request->to_email)->queue(new Invoice($order));
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
     * List the orders that a user has sent.
     *
     * @param   \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function showOrders(Request $request)
    {
        $orders = Auth::user()->transactions()->latest()->get();
        return response()->json([
            'success' => true,
            'orders' => $orders->all(),
        ]);
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
    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'tx_hash' => 'required|size:64',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->errors()->all(),
            ], 400);
        }

        $order = Transaction::find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'no order found with that id',
                'order_id' => $order->id,
            ], 400);
        }
        $order->tx_hash = $request->tx_hash;
        $order->save();
        return response()->json([
            'success' => true,
            'message' => 'order updated successful',
            'order_id' => $order->id,
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

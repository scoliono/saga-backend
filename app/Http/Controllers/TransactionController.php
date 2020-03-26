<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusUpdated;
use App\JWTHelper as API;
use App\Transaction;
use App\User;
use App\Mail\InvoiceConfirmation;
use App\Mail\Receipt;
use App\Rules\ValidETHAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    private $api;

    public function __construct()
    {
        $this->api = new API;
    }

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
            'receipt_list' => 'required|string|json',
            // Discounts should be NEGATIVE (e.g. '-0.2'), taxes or duties should be POSITIVE (e.g. '0.09')
            'discount_list' => 'nullable|string|json',
            'memo' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->errors(),
            ], 422);
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
            ], 422);
        }

        //NOTE: dont use bcmath functions to do calculations, as they don't round off
        try {
            $receipt = json_decode($request->receipt_list, false, 512, JSON_THROW_ON_ERROR);
            if (count($receipt) < 1) {
                return response()->json([
                    'success' => false,
                    'errors' => [ 'receipt_list cannot be empty' ],
                ], 422);
            }
            $tot = 0.0;
            foreach ($receipt as $item) {
                if (!isset($item->name) || !isset($item->quantity) || !isset($item->rate)) {
                    return response()->json([
                        'success' => false,
                        'errors' => [
                            'receipt_list must have a name, rate and quantity for every item'
                        ],
                    ], 422);
                } else if (!is_numeric($item->rate)) {
                    return response()->json([
                        'success' => false,
                        'errors' => [ 'receipt_list rates must all be numeric' ],
                    ], 422);
                } else if (round($item->rate, 2) != $item->rate) {
                    return response()->json([
                        'success' => false,
                        'errors' => [ 'receipt_list rates cannot have more than two decimal places' ],
                    ], 422);
                } else if (!is_numeric($item->quantity) || $item->quantity <= 0) {
                    return response()->json([
                        'success' => false,
                        'errors' => [ 'receipt_list item quantities must be numerical, greater than 0' ],
                    ], 422);
                } else {
                    $unit = $item->rate * $item->quantity;
                    $tot += $unit;
                }
            }
            $discounts = null;
            if ($request->discount_list) {
                $discounts = json_decode($request->discount_list, false, 512, JSON_THROW_ON_ERROR);
                $net_discount = 1.0;
                foreach ($discounts as $discount) {
                    if (!is_numeric($discount->rate)) {
                        return response()->json([
                            'success' => false,
                            'errors' => [ 'Discounts must be numerical' ],
                        ], 422);
                    } else if (round($discount->rate, 4) != $discount->rate) {
                        return response()->json([
                            'success' => false,
                            'errors' => [ 'Discount percentages cannot have more than two decimal places' ],
                        ]);
                    } else {
                        $net_discount += $discount->rate;
                    }
                }
                $tot *= $net_discount;
            }
            $tot = round($tot, 2);
            if ($tot !== round($request->value, 2)) {
                return response()->json([
                    'success' => false,
                    'errors' => [ 'Prices and discounts don\'t add up to a value of '. $request->value .' SAGA' ],
                ], 422);
            }
            if ($from) {
                $order = Transaction::create([
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
                    'discount_list' => $discounts,
                    'memo' => $request->memo,
                ]);
                // don't queue until there's a way to notify merchant of failed jobs
                Mail::to($from->email)->send(new InvoiceConfirmation($order));
            } else {
                $order = Transaction::create([
                    'to_id' => $to->id,
                    'to_address' => $request->to_address,
                    'from_name' => $request->from_name,
                    'from_email' => $request->from_email,
                    'value' => $tot,
                    'receipt_list' => $receipt,
                    'discount_list' => $discounts,
                    'memo' => $request->memo,
                ]);
                Mail::to($request->from_email)->send(new InvoiceConfirmation($order));
            }
            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [ $e->getMessage() ],
            ], 500);
        }
    }

    /**
     * Show the form for a customer to confirm an invoice.
     *
     * @param   \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function showConfirmation(Request $request, $id)
    {
        $tx = Transaction::with('merchant')->findOrFail($id);
        if ($tx->completed()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment has already been completed.',
            ], 422);
        }
        return response()->json([
            'success' => true,
            'tx' => $tx->toPublicArray()
        ]);
    }

    /**
     * The customer confirms he will pay the invoice,
     * so the merchant's info is sent to the event listener daemon.
     *
     * @param   \Illuminate\Http\Request    $request
     * @return  \Illuminate\Http\Response
     */
    public function confirm(Request $request, $id)
    {
        $order = Transaction::findOrFail($id);
        try {
            $url = $this->api->createPayment($order);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        return response()->json([
            'success' => true,
            'url' => $url
        ]);
    }

    /**
     * Generate a response from a list of order IDs.
     *
     * @param   array   $ids
     * @return  \Illuminate\Http\Response
     */
    private function showOrders($ids)
    {
        $orders = Transaction::find($ids);
        foreach ($orders as &$order) {
            $order->customer = $order->customer
                ? collect($order->customer->toPublicArray())
                : null;
        }
        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * List the orders that a user has sent.
     *
     * @param   \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function showOutgoingOrders(Request $request)
    {
        return $this->showOrders(
            Auth::user()->outgoingOrders()->latest()->pluck('id')
        );
    }

    /**
     * List the orders that a user must pay.
     *
     * @param   \Illuminate\Http\Request    $request
     * @return  \Illuminate\Http\Response
     */
    public function showIncomingOrders(Request $request)
    {
        return $this->showOrders(
            Auth::user()->incomingOrders()->latest()->pluck('id')
        );
    }

    /**
     * Export a user's orders within a date range to a CSV file.
     *
     * @param   \Illuminate\Http\Request    $request
     * @return  \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'start' => 'required|date|before_or_equal:end',
            'end' => 'required|date|before_or_equal:now',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->errors()
            ], 422);
        }

        $tx_list = Auth::user()
            ->outgoingOrders
            ->whereBetween('created_at', [$request->start, $request->end])
            ->toArray();
        $cols = Schema::getColumnListing('transactions');
        $labels = [];
        foreach ($cols as $col) {
            $labels[] = __('tx.'. $col);
        }

        $callback = function () use ($tx_list, $labels, $cols) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $labels);
            foreach ($tx_list as $tx) {
                $row = [];
                foreach ($cols as $col) {
                    $item = $tx[$col];
                    if (is_array($item)) {
                        $item = json_encode($item);
                    }
                    $row[] = $item;
                }
                fputcsv($out, $row);
            }
            fclose($out);
        };

        $filename = "SAGA_{$request->start}_{$request->end}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Disposition' => "attachment; filename=$filename",
            'Expires' => '0',
            'Pragma' => 'no-cache',
        ];

        return response()->stream($callback, 200, $headers)->send();
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
            'order_id' => 'required|integer',
            'from' => [ 'required', new ValidETHAddress ],
            'to' => [ 'required', new ValidETHAddress ],
            'payment_status' => 'required|string|in:confirmed,expired,failed',
            'tx_id' => 'required|string|size:66',
            'value' => 'required|numeric|min:0.01'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors()->first(),
            ], 422);
        }

        $id = Str::after($json['order_id'], $this->api->client_id());
        $order = Transaction::find($id);
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => "No transaction with id $id",
            ], 404);
        }

        event(new PaymentStatusUpdated($order, $json));
        if ($json['payment_status'] === 'confirmed') {
            $order->tx_hash = $json['tx_id'];
            $order->from_address = $json['from'];
            $order->save();

            Mail::to($order->from_email)
                ->send(new Receipt($order, true));

            Mail::to($order->merchant)
                ->send(new Receipt($order, false));
        }

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

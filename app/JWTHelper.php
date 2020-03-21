<?php

namespace App;

use \GuzzleHttp\Client;
use \GuzzleHttp\RequestOptions;
use App\Transaction;
use App\Events\PaymentStatusUpdated;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class JWTHelper
{
    /**
     * A simple wrapper class to simplify API requests
     * involving JWT.
     */

    private $client;
    private $client_id;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('app.api'),
            'timeout' => 10.0,
        ]);
        $this->client_id = Cache::get('jwt_client_id');
    }

    /**
     * Make a request to the backend API.
     * Automatically handles JWT tokens.
     *
     * @param string $method
     * @param string $path
     * @param array $args
     * @return object
     * @throws \ErrorException
     */
    protected function request(string $method, string $path, array $args = [])
    {
        Artisan::call('jwt:refresh');
        $response = $this->client->request($method, $path, [
            RequestOptions::JSON => $args,
            'headers' => [
                'Authorization' => 'Bearer ' . Cache::get('jwt_token'),
            ]
        ]);
        $json = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
        if (isset($json->status) && $json->status === 'success') {
            return $json->data;
        } else {
            throw new \ErrorException("Error in API call: $json->message");
        }
    }

    /**
     * Creates an invoice for the daemon to watch for. Returns a
     * URL for the customer to enter the address he will pay with.
     *
     * @param Transaction $tx
     * @return string
     */
    public function createPayment(Transaction $tx)
    {
        $response = $this->api->request('POST', 'payments', [
            'order_id' => $tx->id,
            'receiver' => $tx->to_address,
            'receiver_name' => $tx->customer ?
                $tx->customer->getFullName() : $tx->from_name,
            'value' => round($tx->value, 2),
            'symbol' => 'USD',
            'expire_time' => time() + 30,
        ]);
        return $response->redirect_page;
    }

    /**
     * Gets payment info from the daemon and updates our own
     * records of the transaction accordingly.
     * *TEMPORARY*
     *
     * @param int $id
     * @return Transaction
     */
    public function getPayment(int $id)
    {
        $tx = Transaction::findOrFail($id);
        $response = $this->request('GET', "payments/{$this->client_id}$id");
        if ($response->tx_id && !$tx->tx_hash) {
            $tx->tx_hash = $response->tx_id;
            $tx->from_address = $response->sender;
            $tx->save();
            event(new PaymentStatusUpdated($tx->id, [
                'payment_status' => 'confirmed',
                'value' => $tx->value,
                'from' => $tx->from_address,
                'to' => $tx->to_address,
                'tx_id' => $tx->tx_hash
            ]));
        }
        return $tx;
    }
}

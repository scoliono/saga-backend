<?php

namespace App;

use \GuzzleHttp\Client;
use \GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class JWTHelper
{
    /**
     * A simple wrapper class to simplify API requests
     * involving JWT.
     */

    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('app.api'),
            'timeout' => 10.0,
        ]);
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
    public function request(string $method, string $path, array $args = [])
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

}

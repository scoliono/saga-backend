<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SignInJWTClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:signin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sign in the JWT client.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('app.api'),
            'timeout' => 10.0,
        ]);
        $response = $client->request('POST', 'users/signin', [
            \GuzzleHttp\RequestOptions::JSON => [
                'account' => env('JWT_ACCOUNT'),
                'password' => env('JWT_PASSWORD'),
            ],
        ]);
        $json = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
        if (isset($json->status) && $json->status === 'success') {
            Cache::put('jwt_client_id', $json->data->client_id);
            Cache::put('jwt_client_secret', $json->data->client_secret);
            $this->info('Successfully signed in backend client.');
        } else {
            throw new \ErrorException("Failed to sign in client to backend: $json->message");
        }
    }
}

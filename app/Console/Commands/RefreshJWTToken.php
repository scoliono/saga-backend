<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshJWTToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh JWT token for backend API';

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
            'timeout' => 3.0,
        ]);
        $response = $client->request('POST', 'users/signin', [
            \GuzzleHttp\RequestOptions::JSON => [
                'email' => env('JWT_EMAIL'),
                'password' => env('JWT_PASSWORD'),
            ],
        ]);
        $json = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
        if (isset($json->status) && $json->status === 'success') {
            $jwt = $json->data->jwt_token;
            Cache::put('jwt_token', $jwt, 1440);
            $this->info('Successfully refreshed JWT token');
            if ($json->data->message) {
                $this->info('Message: '. $json->data->message);
            }
            return $jwt;
        } else {
            $this->error('Failed to refresh JWT token with status: '. $json->status);
            if ($json->message) {
                $this->error('Message: '. $json->message);
            }
            return NULL;
        }
    }
}

<?php

namespace App\Console\Commands;

use \Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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
        if (!Cache::get('jwt_client_id') || !Cache::get('jwt_client_secret')) {
            $this->info('Signing in JWT client...');
            $this->call('jwt:signin');
        }
        $jwt = JWT::encode([
            'client_id' => Cache::get('jwt_client_id'),
            'iat' => time(),
            'exp' => time() + 20,
        ], Cache::get('jwt_client_secret'));
        Cache::put('jwt_token', $jwt);
        $this->info('Successfully refreshed JWT token.');
        return $jwt;
    }
}

<?php

namespace Tests\Unit;

use App\JWTHelper as API;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use \Firebase\JWT\JWT;

class GenerateJWTTest extends TestCase
{
    /**
     * Assert that the JWT token generated/signed on our end
     * matches that signed by the backend server.
     *
     * @return void
     */
    public function testTokensMatch()
    {
        Artisan::call('jwt:refresh');

        $api = new API;
        $me = Cache::get('jwt_client_id');
        $secret = Cache::get('jwt_client_secret');

        $response = $api->request('POST', 'users/genjwt', [
            'client_id' => $me,
        ]);
        $decoded = JWT::decode($response->token, $secret, ['HS256']);
        $encoded = JWT::encode([
            'client_id' => $me,
            'iat' => $decoded->iat,
            'exp' => $decoded->exp,
        ], $secret);

        // check the payloads are identical
        $first = explode('.', $response->token)[1];
        $second = explode('.', $encoded)[1];

        $this->assertEquals($first, $second);
    }
}

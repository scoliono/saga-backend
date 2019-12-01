<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'user' => $request->user(),
    ]);
});

Route::post('/payments/create', 'TransactionController@create')->middleware('auth:api', 'verified', 'kyc');
Route::get('/payments/all', 'TransactionController@showOrders')->middleware('auth:api');
Route::post('/payments', 'TransactionController@update')->middleware('eventlistener')->name('payments.update');
Route::get('/payments/guest/{id}', 'TransactionController@showCreateGuest');
Route::post('/payments/guest/{id}', 'TransactionController@createGuest');

// Authentication
Route::post('/login', 'Auth\LoginController@login')->name('login');
Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
Route::post('/register', 'Auth\RegisterController@register')->name('register');

// Password reset
Route::post('/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::post('/password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

// Email verification
Route::get('/email/verify/{id}', 'Auth\VerificationController@verify')->name('verification.verify');
Route::post('/email/resend', 'Auth\VerificationController@resend')->name('verification.resend');

// Profile settings
Route::post('/profile/update/avatar', 'ProfileController@updateAvatar')->middleware('auth:api');
Route::post('/profile/clear/avatar', 'ProfileController@clearAvatar')->middleware('auth:api');
Route::post('/profile/update/id', 'ProfileController@updateID')->middleware('auth:api');
Route::post('/profile/update/personal', 'ProfileController@updateInfo')->middleware('auth:api');
Route::post('/profile/update/wallets', 'ProfileController@updateWallets')->middleware('auth:api');

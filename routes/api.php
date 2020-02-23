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

Route::middleware('auth:airlock')->get('/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'user' => $request->user(),
    ]);
})->name('user.get');

// tx
Route::post('/order', 'TransactionController@create')->middleware('auth:airlock', 'verified', 'kyc');
Route::get('/order/incoming', 'TransactionController@showIncomingOrders')->middleware('auth:airlock');
Route::get('/order/outgoing', 'TransactionController@showOutgoingOrders')->middleware('auth:airlock');
Route::get('/order/export', 'TransactionController@export')->middleware('auth:airlock');
Route::post('/order/{id}/confirm', 'TransactionController@confirm')->middleware('signed')->name('payments.confirm');
Route::post('/order/{id}', 'TransactionController@update')->middleware('eventlistener')->name('payments.update');

// Authentication
Route::post('/login', 'Auth\LoginController@login')->name('login');
Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
Route::post('/register', 'Auth\RegisterController@register')->name('register');

// Password reset
Route::post('/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::post('/password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

// Email verification
Route::get('/email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('verification.verify');
Route::post('/email/resend', 'Auth\VerificationController@resend')->name('verification.resend');

// Profile settings
Route::post('/profile/avatar', 'ProfileController@updateAvatar')->middleware('auth:airlock');
Route::delete('/profile/avatar', 'ProfileController@clearAvatar')->middleware('auth:airlock');
Route::post('/profile/id', 'ProfileController@updateID')->middleware('auth:airlock');
Route::post('/profile/personal', 'ProfileController@updateInfo')->middleware('auth:airlock');
Route::post('/profile/contact', 'ProfileController@updateContactInfo')->middleware('auth:airlock');
Route::post('/profile/wallets', 'ProfileController@updateWallets')->middleware('auth:airlock');

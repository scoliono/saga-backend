<?php

namespace App\Http\Middleware;

use Closure;
use App\Rules\ValidBTCAddress;
use App\Rules\ValidETHAddress;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CheckVerificationStatus
{
    /**
     * Get a validator for an incoming verification request.
     *
     * @param \App\User  $user
     * @return \Illuminate\Support\Facades\Validator
     */
    protected function validator(User $user)
    {
        $v = Validator::make($user->toArray(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'btc' => 'required_without:eth',
            'eth' => 'required_without:btc',
            'birthday' => 'required|before:13 years ago', // idk
        ]);
        $v->sometimes('btc', new ValidBTCAddress, function ($input) {
            return isset($input['btc']);
        });
        $v->sometimes('eth', new ValidETHAddress, function ($input) {
            return isset($input['eth']);
        });

        return $v;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->verified) {
            $validator = $this->validator(Auth::user());
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 400);
            } else {
                return $next($request);
            }
        } else {
            return response()->json([
                'success' => false,
                'errors' => [
                    'You cannot complete this action because your ' .
                    'personal information has not yet been verified.'
                ]
            ], 401);
        }
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * The user has been authenticated.
     *
     * @param \Illuminate\Http\Request  $request
     * @param mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //TODO: doesn't actually matter if user logs in as merchant/customer
        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $user->createToken('saga')->accessToken,
        ]);
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|email|string',
            'password' => 'required|string',
            'personal' => 'nullable',
        ]);
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return response()->json([
            'success' => false,
            'errors' => [
                $this->username() => trans('auth.failed'),
            ]
        ], 401);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();

            $request->session()->invalidate();
        }

        return response()->json([
            'success' => true,
            'user' => null,
        ]);
    }
}

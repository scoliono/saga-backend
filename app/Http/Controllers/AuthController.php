<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $rules = Validator::make($request->all(), [
            'email' => 'required|email|string|max:255|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|confirmed|min:8',
        ]);
        if ($rules->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $rules->errors()->all(),
            ], 400);
        }
        $user = User::create([
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'password' => Hash::make($request->input('password')),
        ]);
        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $user->createToken('saga')->accessToken,
        ]);
    }

    public function login(Request $request)
    {
        $rules = Validator::make($request->all(), [
            'email' => 'required|exists:users',
            'password' => 'required',
        ]);
        if ($rules->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $rules->errors()->all(),
            ], 400);
        }
        $data = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];
        if (Auth::attempt($data)) {
            $user = Auth::user();
            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $user->createToken('saga')->accessToken,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'errors' => ['Incorrect email or password.']
            ], 401);
        }
    }

    public function logout()
    {
        if (Auth::check()) {
            $token = Auth::user()->token();
            if ($token) {
                $token->revoke();
            }
        }
        return response()->json([
            'success' => true
        ]);
    }
}

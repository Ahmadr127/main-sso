<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Handle API login for clients (like Mobile Apps).
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $login_type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $login_type => $request->login,
            'password'  => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Create a Passport token. 
            // In a real OAuth flow this would be done via Personal Access Client or Password Grant, 
            // but for simplicity of a direct API login we use createToken.
            $tokenResult = $user->createToken('Mobile App Access Token');
            $token = $tokenResult->accessToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => [
                    'user' => [
                        'id'       => $user->id,
                        'name'     => $user->name,
                        'username' => $user->username,
                        'email'    => $user->email,
                    ],
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                ]
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Unauthorized. Invalid credentials.',
        ], 401);
    }
}

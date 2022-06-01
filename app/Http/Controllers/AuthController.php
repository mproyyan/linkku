<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('main')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credential = $request->validate([
            'email' => 'required|string|email|exists:users,email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credential)) {
            return response([
                'error' => 'The Provided credentials are not correct'
            ], 422);
        }

        $user = Auth::user();
        $token = $user->createToken('main')->plainTextToken;

        return response([
            'user' => new UserResource($user),
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        // Revoke the token that was used to authenticate the current request...
        $user->tokens()->delete();

        return response([
            'success' => true
        ], 200);
    }
}

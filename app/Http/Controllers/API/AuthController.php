<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Register API
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');
        // $token = $user->createToken('auth_token')->plainTextToken;

        $loginToken = Str::random(60);
        $user->login_token = $loginToken;
        $user->login_token_expires_at = now()->addMinutes(30);
        $user->save();


        return response()->json([
            'access_token' => $loginToken,
            'user' => $user,
            'role' => $user->getRoleNames()->first(),
        ]);
    }

    // Login API
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // $token = $user->createToken('auth_token')->plainTextToken;


        $loginToken = Str::random(60);
        $user->login_token = $loginToken;
        $user->login_token_expires_at = now()->addMinutes(30);
        $user->save();

        return response()->json([
            'access_token' => $loginToken,
            'user' => $user,
            'role' => $user->getRoleNames()->first(),
        ]);
    }

    // Logout API (optional)
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }



}
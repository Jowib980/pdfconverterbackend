<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;

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

        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(5)
        ]);


        $user->assignRole('user');

        Mail::to($user->email)->send(new SendOtpMail($otp));

        // $token = $user->createToken('auth_token')->plainTextToken;

        $loginToken = Str::random(60);
        $user->login_token = $loginToken;
        $user->login_token_expires_at = now()->addMinutes(30);
        $user->save();


        return response()->json(['message', 'Registered successfully']);
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

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        $user->email_verified_at = now(); // optional
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        // Optionally generate token now
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully.',
            'access_token' => $token,
            'user' => $user,
            'role' => $user->getRoleNames()->first(),
        ]);
    }



}
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
use Illuminate\Support\Facades\Auth;

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

    public function currentUser(Request $request)
    {
        $authorization = $request->header('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = str_replace('Bearer ', '', $authorization);

        $user = \App\Models\User::where('login_token', $token)
            ->where('login_token_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        // Manually set the authenticated user if you want to use Auth::user()
        auth()->setUser($user);

        // Load related data
        $user->load([
            'convertedDocuments' => function ($query) {
                $query->select('id', 'user_id', 'file_type', 'convert_into', 'original_name', 'converted_name', 'created_at');
            },
            'paymentDetails' => function ($query) {
                $query->select('id', 'user_id', 'payer_email', 'plan_type', 'plan_amount', 'transaction_id', 'transaction_status', 'payment_date', 'gateway')->latest()->limit(1);
            }
        ]);

        return response()->json([
            'user' => $user
        ]);
    }


}
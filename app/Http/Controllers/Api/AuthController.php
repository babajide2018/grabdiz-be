<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{

    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Use password_verify directly to support $2a$ bcrypt hashes from existing database
        if (!$user || !password_verify($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid email or password',
            ], 401);
        }

        // Create token using Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Prepare user data
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'postcode' => $user->postcode,
            'country' => $user->country,
            'role' => $user->role,
            'profile_picture' => $user->profile_picture,
            'dateOfBirth' => $user->date_of_birth,
        ];


        // Set cookie
        $cookie = cookie(
            'auth_token',
            $token,
            60 * 24 * 7, // 7 days
            '/',
            null,
            false, // httpOnly - set to false for client-side access
            false, // secure - set to true in production with HTTPS
            false, // sameSite
            'Lax'
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $userData,
                'token' => $token,
            ],
        ])->cookie($cookie);
    }



    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'firstName' => 'nullable|string|max:100',
            'lastName' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        // Hash password using bcrypt (compatible with existing $2a$ format)
        $passwordHash = password_hash($request->password, PASSWORD_BCRYPT);

        $user = User::create([
            'email' => $request->email,
            'password_hash' => $passwordHash,
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'phone' => $request->phone,
            'role' => 'customer',
            'email_verified' => false,
        ]);

        // Create token using Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Prepare user data
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'postcode' => $user->postcode,
            'country' => $user->country,
            'role' => $user->role,
            'profile_picture' => $user->profile_picture,
            'dateOfBirth' => $user->date_of_birth,
        ];

        // Set cookie
        $cookie = cookie(
            'auth_token',
            $token,
            60 * 24 * 7, // 7 days
            '/',
            null,
            false, // httpOnly - set to false for client-side access
            false, // secure - set to true in production with HTTPS
            false, // sameSite
            'Lax'
        );

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $userData,
                'token' => $token,
            ],
        ], 201)->cookie($cookie);
    }



    public function me(Request $request)
    {
        $user = $request->user();

        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'postcode' => $user->postcode,
            'country' => $user->country,
            'role' => $user->role,
            'profile_picture' => $user->profile_picture,
            'dateOfBirth' => $user->date_of_birth,
        ];

        return response()->json([
            'success' => true,
            'data' => $userData,
        ]);
    }
}

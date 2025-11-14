<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create user with unverified civitas status
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'user',
                'is_verified_civitas' => false,
            ]);

            // Create wallet for the user
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'total_income' => 0,
                'total_withdrawn' => 0,
            ]);

            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'is_verified_civitas' => $user->is_verified_civitas,
                    ],
                    'token' => $token,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal. Silakan coba lagi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user and generate token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Attempt authentication
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $user = Auth::user();

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_verified_civitas' => $user->is_verified_civitas,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_verified_civitas' => $user->is_verified_civitas,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
        ], 200);
    }

    /**
     * Update authenticated user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Update user data
            $user->update([
                'name' => $request->name ?? $user->name,
                'phone' => $request->phone ?? $user->phone,
            ]);

            // Update password if provided
            if ($request->filled('password')) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_verified_civitas' => $user->is_verified_civitas,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user's wallet information.
     */
    public function wallet(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => (float) $wallet->balance,
                'available_balance' => (float) $wallet->available_balance,
                'total_income' => (float) $wallet->total_income,
                'total_withdrawn' => (float) $wallet->total_withdrawn,
            ],
        ], 200);
    }
}

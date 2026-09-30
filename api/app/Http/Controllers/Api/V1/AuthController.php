<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user account and issue an initial Bearer token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Authenticate user credentials and issue a new Bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse(
                'INVALID_CREDENTIALS',
                'These credentials do not match our records.',
                null,
                401
            );
        }

        $deviceName = $request->device_name ?: 'auth-token';
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Revoke the currently authenticated Bearer token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Retrieve authenticated user profile with active report quotas.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'active_reports_count' => $user->activeReportsCount(),
            'active_reports_quota' => 3,
        ]);
    }
}

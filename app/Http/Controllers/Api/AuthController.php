<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'phone' => $request->string('phone')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => $request->input('role', 'customer'),
            'kyc_status' => 'pending',
            'is_active' => true,
        ]);

        $token = $user->createToken($request->userAgent() ?: 'api-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => UserResource::make($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->string('email')->lower()->toString())
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is inactive.'],
            ]);
        }

        $token = $user->createToken($request->userAgent() ?: 'api-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => UserResource::make($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => UserResource::make($request->user()),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'password' => $request->string('password')->toString(),
        ])->save();

        $user->tokens()
            ->when($request->user()?->currentAccessToken(), fn ($query, $token) => $query->whereKeyNot($token->getKey()))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}

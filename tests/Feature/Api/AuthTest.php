<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a user and returns a bearer token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+2250700000000',
        'role' => 'customer',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'phone', 'role', 'kyc_status'],
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'role' => 'customer',
        'kyc_status' => 'pending',
    ]);
});

it('logs in an active user and returns a token', function () {
    $user = User::factory()->create([
        'email' => 'seller@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', $user->email);
});

it('returns the authenticated user profile', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-suite')->plainTextToken;

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me');

    $response
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', $user->email);
});

it('logs out by revoking the current access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-suite');

    expect($user->tokens()->count())->toBe(1);

    $this
        ->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('updates the password for an authenticated user', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $token = $user->createToken('test-suite')->plainTextToken;

    $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/v1/auth/password', [
            'current_password' => 'old-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('new-password123', $user->fresh()->password))->toBeTrue();
});

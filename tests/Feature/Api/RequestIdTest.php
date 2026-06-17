<?php

use App\Models\User;

it('preserves a provided request id on successful api responses', function () {
    $this
        ->withHeaders([
            'X-Request-Id' => 'req-health-001',
        ])
        ->getJson('/api/health')
        ->assertOk()
        ->assertHeader('X-Request-Id', 'req-health-001');
});

it('returns a generated request id in api error responses after middleware execution', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
    ]);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$seller->createToken('request-id-suite')->plainTextToken,
        ])
        ->getJson('/api/v1/seller/finance/summary')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'NOT_FOUND');

    expect($response->json('meta.request_id'))->not->toBeEmpty();
    expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();
});

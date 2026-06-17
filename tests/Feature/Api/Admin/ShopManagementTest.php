<?php

use App\Models\Shop;
use App\Models\User;

it('allows an admin to list inspect and moderate shops', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $shop = Shop::factory()->create([
        'name' => 'Pending Shop',
        'slug' => 'pending-shop',
        'status' => 'pending',
    ]);
    $headers = fn () => [
        'Authorization' => 'Bearer '.$admin->createToken('admin-shop-suite')->plainTextToken,
    ];

    $this
        ->withHeaders($headers())
        ->getJson('/api/v1/admin/shops?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data.shops')
        ->assertJsonPath('data.shops.0.id', $shop->id);

    $this
        ->withHeaders($headers())
        ->getJson('/api/v1/admin/shops/'.$shop->id)
        ->assertOk()
        ->assertJsonPath('data.shop.slug', 'pending-shop');

    $this
        ->withHeaders($headers())
        ->patchJson('/api/v1/admin/shops/'.$shop->id, [
            'name' => 'Approved Shop',
            'status' => 'active',
            'city' => 'Abidjan',
        ])
        ->assertOk()
        ->assertJsonPath('data.shop.slug', 'approved-shop')
        ->assertJsonPath('data.shop.status', 'active')
        ->assertJsonPath('data.shop.city', 'Abidjan');
});

it('forbids non admins from moderating shops', function () {
    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
    $shop = Shop::factory()->create();
    $headers = [
        'Authorization' => 'Bearer '.$customer->createToken('forbidden-admin-shop')->plainTextToken,
    ];

    $this
        ->withHeaders($headers)
        ->getJson('/api/v1/admin/shops')
        ->assertForbidden();

    $this
        ->withHeaders($headers)
        ->patchJson('/api/v1/admin/shops/'.$shop->id, [
            'status' => 'suspended',
        ])
        ->assertForbidden();
});

<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

it('allows an admin to create list show update and delete products', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $shop = Shop::factory()->create(['status' => 'active']);
    $category = Category::factory()->create(['is_active' => true]);
    $headers = fn () => [
        'Authorization' => 'Bearer '.$admin->createToken('admin-product-suite')->plainTextToken,
    ];

    $createResponse = $this
        ->withHeaders($headers())
        ->postJson('/api/v1/admin/products', [
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Admin Laptop',
            'sku' => 'ADMIN-LAPTOP-001',
            'short_description' => 'Managed by admin',
            'description' => 'Admin controlled product.',
            'price' => 1999.99,
            'stock' => 12,
            'status' => 'published',
            'is_active' => true,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.product.name', 'Admin Laptop')
        ->assertJsonPath('data.product.slug', 'admin-laptop');

    $product = Product::query()->firstOrFail();

    $this
        ->withHeaders($headers())
        ->getJson('/api/v1/admin/products?shop_id='.$shop->id)
        ->assertOk()
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.id', $product->id);

    $this
        ->withHeaders($headers())
        ->getJson('/api/v1/admin/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.product.id', $product->id);

    $this
        ->withHeaders($headers())
        ->patchJson('/api/v1/admin/products/'.$product->id, [
            'name' => 'Admin Laptop Pro',
            'status' => 'archived',
            'is_active' => false,
            'stock' => 4,
        ])
        ->assertOk()
        ->assertJsonPath('data.product.slug', 'admin-laptop-pro')
        ->assertJsonPath('data.product.status', 'archived')
        ->assertJsonPath('data.product.is_active', false);

    $this
        ->withHeaders($headers())
        ->deleteJson('/api/v1/admin/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Product::query()->count())->toBe(0);
});

it('forbids non admins from managing products as admin', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $product = Product::factory()->create();
    $headers = [
        'Authorization' => 'Bearer '.$seller->createToken('forbidden-admin-product')->plainTextToken,
    ];

    $this
        ->withHeaders($headers)
        ->getJson('/api/v1/admin/products')
        ->assertForbidden();

    $this
        ->withHeaders($headers)
        ->patchJson('/api/v1/admin/products/'.$product->id, [
            'status' => 'archived',
        ])
        ->assertForbidden();
});

<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

function sellerAuthHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('test-suite')->plainTextToken,
    ];
}

it('allows a seller to create and update their shop', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);

    $createResponse = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/shops', [
            'name' => 'Marketify Store',
            'description' => 'Best store in town',
            'phone' => '+2250701010101',
            'email' => 'shop@example.com',
            'address' => 'Abidjan',
            'city' => 'Abidjan',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.shop.name', 'Marketify Store')
        ->assertJsonPath('data.shop.status', 'pending');

    $shop = Shop::query()->firstOrFail();

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/shops/'.$shop->id, [
            'name' => 'Marketify Mega Store',
            'city' => 'Yamoussoukro',
        ])
        ->assertOk()
        ->assertJsonPath('data.shop.name', 'Marketify Mega Store')
        ->assertJsonPath('data.shop.city', 'Yamoussoukro');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->getJson('/api/v1/seller/shops/me')
        ->assertOk()
        ->assertJsonPath('data.shop.slug', 'marketify-mega-store');
});

it('allows a seller to create and update their own product', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $category = Category::factory()->create(['is_active' => true]);

    $createResponse = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products', [
            'category_id' => $category->id,
            'name' => 'Gaming Laptop',
            'sku' => 'LAPTOP-001',
            'short_description' => 'Powerful laptop',
            'description' => 'A powerful laptop for gamers.',
            'price' => 2500,
            'stock' => 7,
            'status' => 'published',
            'is_active' => true,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.product.shop_id', $shop->id)
        ->assertJsonPath('data.product.slug', 'gaming-laptop');

    $product = Product::query()->firstOrFail();

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id, [
            'name' => 'Gaming Laptop Pro',
            'price' => 2800,
            'stock' => 5,
        ])
        ->assertOk()
        ->assertJsonPath('data.product.slug', 'gaming-laptop-pro')
        ->assertJsonPath('data.product.price', '2800.00');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->getJson('/api/v1/seller/products')
        ->assertOk()
        ->assertJsonCount(1, 'data.products');
});

it('forbids a seller from updating another sellers product', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $otherSeller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $otherShop = Shop::factory()->create(['user_id' => $otherSeller->id, 'status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $otherShop->id]);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id, [
            'name' => 'Hijacked Product',
        ])
        ->assertForbidden();
});

it('forbids non sellers from creating shops', function () {
    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

    $this
        ->withHeaders(sellerAuthHeaders($customer))
        ->postJson('/api/v1/seller/shops', [
            'name' => 'Not Allowed Shop',
        ])
        ->assertForbidden();
});

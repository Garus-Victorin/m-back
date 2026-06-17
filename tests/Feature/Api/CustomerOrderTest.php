<?php

use App\Models\DeliveryAddress;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;

it('creates a customer order and reserves stock for a simple product', function () {
    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $address = DeliveryAddress::factory()->create(['user_id' => $customer->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
        'stock' => 8,
        'reserved_stock' => 1,
        'price' => 1000,
    ]);

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$customer->createToken('customer-order')->plainTextToken)
        ->postJson('/api/v1/orders', [
            'delivery_address_id' => $address->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.order.shop_id', $shop->id)
        ->assertJsonPath('data.order.status', 'pending')
        ->assertJsonPath('data.order.subtotal', '2000.00')
        ->assertJsonPath('data.order.total', '2000.00')
        ->assertJsonPath('data.order.items.0.product_id', $product->id)
        ->assertJsonPath('data.order.items.0.quantity', 2);

    $product->refresh();
    expect($product->reserved_stock)->toBe(3);
});

it('creates a customer order with a variant and reserves variant stock', function () {
    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $address = DeliveryAddress::factory()->create(['user_id' => $customer->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
        'stock' => 10,
        'reserved_stock' => 0,
        'price' => 1000,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'attribute_name' => 'size',
        'attribute_value' => 'L',
        'sku' => 'ORDER-VAR-L',
        'extra_price' => 250,
        'stock' => 10,
        'reserved_stock' => 0,
        'position' => 1,
    ]);

    $product->update(['stock' => 10, 'reserved_stock' => 0]);

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$customer->createToken('customer-order-var')->plainTextToken)
        ->postJson('/api/v1/orders', [
            'delivery_address_id' => $address->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 3,
                ],
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.order.items.0.product_variant_id', $variant->id)
        ->assertJsonPath('data.order.items.0.product_variant.id', $variant->id)
        ->assertJsonPath('data.order.items.0.unit_price', '1250.00')
        ->assertJsonPath('data.order.subtotal', '3750.00');

    $product->refresh();
    $variant->refresh();

    expect($variant->reserved_stock)->toBe(3);
    expect($product->reserved_stock)->toBe(3);
});

it('rejects customer orders spanning multiple shops', function () {
    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
    $sellerA = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $sellerB = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shopA = Shop::factory()->create(['user_id' => $sellerA->id, 'status' => 'active']);
    $shopB = Shop::factory()->create(['user_id' => $sellerB->id, 'status' => 'active']);
    $address = DeliveryAddress::factory()->create(['user_id' => $customer->id]);
    $productA = Product::factory()->create(['shop_id' => $shopA->id, 'status' => 'published', 'moderation_status' => 'approved', 'is_active' => true]);
    $productB = Product::factory()->create(['shop_id' => $shopB->id, 'status' => 'published', 'moderation_status' => 'approved', 'is_active' => true]);

    $this
        ->withHeader('Authorization', 'Bearer '.$customer->createToken('customer-order-multi')->plainTextToken)
        ->postJson('/api/v1/orders', [
            'delivery_address_id' => $address->id,
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 1],
                ['product_id' => $productB->id, 'quantity' => 1],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

it('forbids non customers from creating customer orders', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $address = DeliveryAddress::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $this
        ->withHeader('Authorization', 'Bearer '.$seller->createToken('seller-order-forbidden')->plainTextToken)
        ->postJson('/api/v1/orders', [
            'delivery_address_id' => $address->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
        ->assertForbidden();
});

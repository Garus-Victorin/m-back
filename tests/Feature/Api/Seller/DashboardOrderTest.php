<?php

use App\Models\Category;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;

function sellerApiHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('test-suite')->plainTextToken,
    ];
}

it('returns seller bootstrap and dashboard summaries', function () {
    Carbon::setTestNow('2026-06-17 10:00:00');

    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'verified',
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    $category = Category::factory()->create(['is_active' => true]);

    Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'status' => 'published',
        'moderation_status' => 'approved',
        'stock' => 8,
    ]);

    Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'status' => 'published',
        'moderation_status' => 'approved',
        'stock' => 0,
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'paid',
        'seller_amount' => 1200,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'ready_for_pickup',
        'seller_amount' => 800,
        'created_at' => now()->subHour(),
        'updated_at' => now()->subHour(),
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'delivered',
        'seller_amount' => 3000,
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->getJson('/api/v1/seller/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.shop.id', $shop->id)
        ->assertJsonPath('data.kyc_status', 'verified')
        ->assertJsonPath('data.capabilities.can_publish_products', true)
        ->assertJsonPath('data.capabilities.can_request_withdrawals', true)
        ->assertJsonPath('data.dashboard.summary.pending_orders_count', 1)
        ->assertJsonPath('data.dashboard.summary.ready_orders_count', 1)
        ->assertJsonPath('data.dashboard.summary.out_of_stock_products_count', 1)
        ->assertJsonPath('data.dashboard.summary.published_products_count', 2)
        ->assertJsonPath('data.dashboard.summary.total_products_count', 2)
        ->assertJsonCount(7, 'data.dashboard.revenue_trend');

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->getJson('/api/v1/seller/dashboard')
        ->assertOk()
        ->assertJsonPath('data.summary.pending_orders_count', 1)
        ->assertJsonPath('data.summary.ready_orders_count', 1)
        ->assertJsonPath('data.summary.out_of_stock_products_count', 1)
        ->assertJsonCount(2, 'data.recent_orders');

    Carbon::setTestNow();
});

it('allows a seller to list, view and mark their order as ready', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'verified',
    ]);

    $customer = User::factory()->create([
        'role' => 'customer',
        'is_active' => true,
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    $address = DeliveryAddress::factory()->create([
        'user_id' => $customer->id,
    ]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'shop_id' => $shop->id,
        'delivery_address_id' => $address->id,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'unit_price' => $product->price,
        'quantity' => 2,
        'total_price' => $product->price * 2,
    ]);

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->getJson('/api/v1/seller/orders?status=paid')
        ->assertOk()
        ->assertJsonCount(1, 'data.orders')
        ->assertJsonPath('data.orders.0.id', $order->id);

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->getJson('/api/v1/seller/orders/'.$order->id)
        ->assertOk()
        ->assertJsonPath('data.order.id', $order->id)
        ->assertJsonPath('data.order.items.0.product_id', $product->id)
        ->assertJsonPath('data.order.delivery_address.id', $address->id);

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->postJson('/api/v1/seller/orders/'.$order->id.'/mark-ready')
        ->assertOk()
        ->assertJsonPath('data.order.status', 'ready_for_pickup');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'ready_for_pickup',
    ]);
});

it('forbids a seller from accessing another sellers order', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $otherSeller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $otherShop = Shop::factory()->create(['user_id' => $otherSeller->id, 'status' => 'active']);
    $order = Order::factory()->create(['shop_id' => $otherShop->id]);

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->getJson('/api/v1/seller/orders/'.$order->id)
        ->assertForbidden();

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->postJson('/api/v1/seller/orders/'.$order->id.'/mark-ready')
        ->assertForbidden();
});

it('rejects mark-ready when the order transition is invalid', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $order = Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);

    $this
        ->withHeaders(sellerApiHeaders($seller))
        ->postJson('/api/v1/seller/orders/'.$order->id.'/mark-ready')
        ->assertStatus(409)
        ->assertJsonPath('message', 'Order cannot be marked ready from status [delivered].');
});

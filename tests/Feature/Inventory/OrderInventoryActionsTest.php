<?php

use App\Actions\Inventory\CommitOrderInventoryAction;
use App\Actions\Inventory\ReleaseOrderInventoryReservationAction;
use App\Actions\Inventory\ReserveOrderInventoryAction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

it('reserves inventory for a simple product order exactly once', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'stock' => 10,
        'reserved_stock' => 1,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 3,
        'unit_price' => $product->price,
        'total_price' => $product->price * 3,
    ]);

    $reservedOrder = app(ReserveOrderInventoryAction::class)->execute($order);

    expect($reservedOrder->inventory_reserved_at)->not->toBeNull();

    $product->refresh();
    expect($product->reserved_stock)->toBe(4);
    expect($product->stock)->toBe(10);

    app(ReserveOrderInventoryAction::class)->execute($order);
    $product->refresh();
    expect($product->reserved_stock)->toBe(4);
});

it('reserves and releases inventory for variant based order items', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'stock' => 8,
        'reserved_stock' => 0,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $firstVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'attribute_name' => 'size',
        'attribute_value' => 'M',
        'sku' => 'INV-M',
        'stock' => 5,
        'reserved_stock' => 0,
        'position' => 1,
    ]);

    $secondVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'attribute_name' => 'size',
        'attribute_value' => 'L',
        'sku' => 'INV-L',
        'stock' => 3,
        'reserved_stock' => 0,
        'position' => 2,
    ]);

    $product->update([
        'stock' => 8,
        'reserved_stock' => 0,
    ]);

    $order = Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $firstVariant->id,
        'product_name' => $product->name,
        'product_sku' => $firstVariant->sku,
        'quantity' => 2,
        'variants' => ['variant_id' => $firstVariant->id, 'size' => 'M'],
        'unit_price' => $product->price,
        'total_price' => $product->price * 2,
    ]);

    $reservedOrder = app(ReserveOrderInventoryAction::class)->execute($order);
    expect($reservedOrder->inventory_reserved_at)->not->toBeNull();

    $product->refresh();
    $firstVariant->refresh();
    $secondVariant->refresh();

    expect($firstVariant->reserved_stock)->toBe(2);
    expect($secondVariant->reserved_stock)->toBe(0);
    expect($product->reserved_stock)->toBe(2);
    expect($product->stock)->toBe(8);

    $releasedOrder = app(ReleaseOrderInventoryReservationAction::class)->execute($order);
    expect($releasedOrder->inventory_released_at)->not->toBeNull();

    $product->refresh();
    $firstVariant->refresh();
    $secondVariant->refresh();

    expect($firstVariant->reserved_stock)->toBe(0);
    expect($secondVariant->reserved_stock)->toBe(0);
    expect($product->reserved_stock)->toBe(0);
    expect($product->stock)->toBe(8);
});

it('commits reserved inventory and decrements final stock', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'stock' => 6,
        'reserved_stock' => 0,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'attribute_name' => 'color',
        'attribute_value' => 'Black',
        'sku' => 'INV-BLK',
        'stock' => 6,
        'reserved_stock' => 0,
        'position' => 1,
    ]);

    $product->update([
        'stock' => 6,
        'reserved_stock' => 0,
    ]);

    $order = Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'picked_up',
        'paid_at' => now(),
        'picked_up_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'product_sku' => $variant->sku,
        'quantity' => 4,
        'variants' => ['variant_id' => $variant->id, 'color' => 'Black'],
        'unit_price' => $product->price,
        'total_price' => $product->price * 4,
    ]);

    app(ReserveOrderInventoryAction::class)->execute($order);
    $committedOrder = app(CommitOrderInventoryAction::class)->execute($order);

    expect($committedOrder->inventory_committed_at)->not->toBeNull();

    $product->refresh();
    $variant->refresh();

    expect($variant->stock)->toBe(2);
    expect($variant->reserved_stock)->toBe(0);
    expect($product->stock)->toBe(2);
    expect($product->reserved_stock)->toBe(0);
});

it('rejects reservation when available stock is insufficient', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'stock' => 2,
        'reserved_stock' => 1,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => $product->price,
        'total_price' => $product->price * 2,
    ]);

    expect(fn () => app(ReserveOrderInventoryAction::class)->execute($order))
        ->toThrow(ConflictHttpException::class, 'Insufficient available stock for product ['.$product->id.'].');
});

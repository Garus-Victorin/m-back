<?php

namespace App\Actions\Order;

use App\Actions\Inventory\ReserveOrderInventoryAction;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateCustomerOrderAction
{
    public function __construct(
        protected ReserveOrderInventoryAction $reserveOrderInventory,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(User $customer, array $attributes): Order
    {
        /** @var DeliveryAddress $deliveryAddress */
        $deliveryAddress = DeliveryAddress::query()
            ->whereKey($attributes['delivery_address_id'])
            ->where('user_id', $customer->id)
            ->firstOrFail();

        /** @var array<int, array<string, mixed>> $items */
        $items = $attributes['items'];

        return DB::transaction(function () use ($customer, $deliveryAddress, $attributes, $items): Order {
            $resolvedItems = collect($items)->map(function (array $item): array {
                /** @var Product $product */
                $product = Product::query()
                    ->with('shop')
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                if (
                    $product->status !== 'published'
                    || $product->moderation_status !== 'approved'
                    || ! $product->is_active
                    || ! $product->shop
                    || $product->shop->status !== 'active'
                ) {
                    throw new ConflictHttpException('One of the requested products is no longer available for ordering.');
                }

                $variant = null;
                $unitPrice = (float) $product->price;
                $variantSnapshot = null;
                $productSku = $product->sku;

                if (! empty($item['product_variant_id'])) {
                    /** @var ProductVariant|null $variant */
                    $variant = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->find($item['product_variant_id']);

                    if (! $variant || ! $variant->is_active) {
                        throw new ConflictHttpException('The requested product variant is unavailable.');
                    }

                    $unitPrice += (float) $variant->extra_price;
                    $productSku = $variant->sku ?: $product->sku;
                    $variantSnapshot = [
                        'variant_id' => $variant->id,
                        'attribute_name' => $variant->attribute_name,
                        'attribute_value' => $variant->attribute_value,
                        'sku' => $variant->sku,
                        'extra_price' => $variant->extra_price,
                    ];
                }

                return [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => round($unitPrice, 2),
                    'product_sku' => $productSku,
                    'variants' => $variantSnapshot,
                ];
            });

            $shopId = $this->resolveSingleShopId($resolvedItems);
            $subtotal = round((float) $resolvedItems->sum(fn (array $item): float => $item['unit_price'] * $item['quantity']), 2);
            $deliveryFee = 0.0;
            $total = round($subtotal + $deliveryFee, 2);
            $platformCommission = round($subtotal * 0.08, 2);
            $sellerAmount = round($subtotal - $platformCommission, 2);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'shop_id' => $shopId,
                'delivery_address_id' => $deliveryAddress->id,
                'delivery_method' => $attributes['delivery_method'] ?? 'home',
                'otp_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'platform_commission' => $platformCommission,
                'seller_amount' => $sellerAmount,
                'delivery_amount' => 0,
                'payment_details' => null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($resolvedItems as $resolvedItem) {
                /** @var Product $product */
                $product = $resolvedItem['product'];
                /** @var ProductVariant|null $variant */
                $variant = $resolvedItem['variant'];
                $quantity = $resolvedItem['quantity'];
                $unitPrice = $resolvedItem['unit_price'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'product_sku' => $resolvedItem['product_sku'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'total_price' => round($unitPrice * $quantity, 2),
                    'variants' => $resolvedItem['variants'],
                ]);
            }

            $order = $this->reserveOrderInventory->execute($order);

            return $order->fresh(['customer', 'items.product', 'items.productVariant', 'deliveryAddress', 'shop']);
        });
    }

    /**
     * @param Collection<int, array<string, mixed>> $resolvedItems
     */
    protected function resolveSingleShopId(Collection $resolvedItems): int
    {
        $shopIds = $resolvedItems->map(fn (array $item): int => $item['product']->shop_id)->unique()->values();

        if ($shopIds->count() !== 1) {
            throw new ConflictHttpException('All order items must belong to the same shop.');
        }

        return (int) $shopIds->first();
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.Str::upper(Str::random(10));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}

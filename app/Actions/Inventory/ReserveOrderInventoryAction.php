<?php

namespace App\Actions\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReserveOrderInventoryAction
{
    public function __construct(
        protected SyncProductInventoryFromVariantsAction $syncProductInventoryFromVariants,
    ) {
    }

    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::query()
                ->with(['items.product', 'items.productVariant'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->inventory_committed_at !== null) {
                throw new ConflictHttpException('Order inventory has already been committed.');
            }

            if ($order->inventory_released_at !== null) {
                throw new ConflictHttpException('Order inventory has already been released.');
            }

            if ($order->inventory_reserved_at !== null) {
                return $this->freshOrder($order);
            }

            $variantProductIds = [];

            foreach ($order->items as $item) {
                $this->reserveItem($item, $variantProductIds);
            }

            foreach (array_unique($variantProductIds) as $productId) {
                $product = Product::query()->lockForUpdate()->findOrFail($productId);
                $this->syncProductInventoryFromVariants->execute($product);
            }

            $order->forceFill([
                'inventory_reserved_at' => now(),
            ])->save();

            return $this->freshOrder($order);
        });
    }

    /**
     * @param array<int, int> $variantProductIds
     */
    protected function reserveItem(OrderItem $item, array &$variantProductIds): void
    {
        $quantity = (int) $item->quantity;
        $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

        if ($item->product_variant_id) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->find($item->product_variant_id);

            if (! $variant) {
                throw new ConflictHttpException('Order item variant could not be found for inventory reservation.');
            }

            $availableStock = $variant->stock - $variant->reserved_stock;

            if ($quantity > $availableStock) {
                throw new ConflictHttpException(sprintf(
                    'Insufficient available stock for variant [%d] on product [%d].',
                    $variant->id,
                    $product->id,
                ));
            }

            $variant->increment('reserved_stock', $quantity);
            $variantProductIds[] = $product->id;

            return;
        }

        $availableStock = $product->stock - $product->reserved_stock;

        if ($quantity > $availableStock) {
            throw new ConflictHttpException(sprintf(
                'Insufficient available stock for product [%d].',
                $product->id,
            ));
        }

        $product->increment('reserved_stock', $quantity);
    }

    protected function freshOrder(Order $order): Order
    {
        return $order->fresh(['customer', 'items.product', 'items.productVariant', 'deliveryAddress', 'shop']);
    }
}

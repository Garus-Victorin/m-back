<?php

namespace App\Actions\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CommitOrderInventoryAction
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

            if ($order->inventory_released_at !== null) {
                throw new ConflictHttpException('Released order inventory cannot be committed anymore.');
            }

            if ($order->inventory_committed_at !== null) {
                return $this->freshOrder($order);
            }

            if ($order->inventory_reserved_at === null) {
                throw new ConflictHttpException('Order inventory must be reserved before it can be committed.');
            }

            $variantProductIds = [];

            foreach ($order->items as $item) {
                $this->commitItem($item, $variantProductIds);
            }

            foreach (array_unique($variantProductIds) as $productId) {
                $product = Product::query()->lockForUpdate()->findOrFail($productId);
                $this->syncProductInventoryFromVariants->execute($product);
            }

            $order->forceFill([
                'inventory_committed_at' => now(),
            ])->save();

            return $this->freshOrder($order);
        });
    }

    /**
     * @param array<int, int> $variantProductIds
     */
    protected function commitItem(OrderItem $item, array &$variantProductIds): void
    {
        $quantity = (int) $item->quantity;
        $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

        if ($item->product_variant_id) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->find($item->product_variant_id);

            if (! $variant) {
                throw new ConflictHttpException('Order item variant could not be found for inventory commit.');
            }

            if ($variant->reserved_stock < $quantity || $variant->stock < $quantity) {
                throw new ConflictHttpException(sprintf(
                    'Variant [%d] does not have enough reserved stock to commit this order.',
                    $variant->id,
                ));
            }

            $variant->decrement('reserved_stock', $quantity);
            $variant->decrement('stock', $quantity);
            $variantProductIds[] = $product->id;

            return;
        }

        if ($product->reserved_stock < $quantity || $product->stock < $quantity) {
            throw new ConflictHttpException(sprintf(
                'Product [%d] does not have enough reserved stock to commit this order.',
                $product->id,
            ));
        }

        $product->decrement('reserved_stock', $quantity);
        $product->decrement('stock', $quantity);
    }

    protected function freshOrder(Order $order): Order
    {
        return $order->fresh(['customer', 'items.product', 'items.productVariant', 'deliveryAddress', 'shop']);
    }
}

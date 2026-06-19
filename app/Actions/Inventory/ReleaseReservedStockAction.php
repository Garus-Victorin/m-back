<?php

namespace App\Actions\Inventory;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class ReleaseReservedStockAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function execute(Order $order, ?string $reason = null): bool
    {
        if ($order->inventory_reserved_at === null) {
            Log::warning('Cannot release stock for order that was never reserved', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        if ($order->inventory_released_at !== null) {
            Log::warning('Stock already released for this order', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        $items = $order->items()->with(['product', 'variant'])->get();

        foreach ($items as $item) {
            if ($item->variant) {
                $this->releaseVariantStock($item->variant, $item->quantity);
            } else {
                $this->releaseProductStock($item->product, $item->quantity);
            }
        }

        $order->update([
            'inventory_released_at' => now(),
            'inventory_release_reason' => $reason,
        ]);

        $this->auditLog->execute(
            action: 'inventory.stock_released',
            actor: null,
            target: $order,
            after: [
                'inventory_released_at' => $order->inventory_released_at,
                'inventory_release_reason' => $reason,
            ]
        );

        Log::info('Reserved stock released for order', [
            'order_id' => $order->id,
            'reason' => $reason,
        ]);

        return true;
    }

    protected function releaseProductStock(Product $product, int $quantity): void
    {
        $product->decrement('reserved_stock', $quantity);

        $this->auditLog->execute(
            action: 'inventory.product_stock_released',
            actor: null,
            target: $product,
            after: [
                'reserved_stock' => $product->reserved_stock,
                'quantity_released' => $quantity,
            ]
        );
    }

    protected function releaseVariantStock(ProductVariant $variant, int $quantity): void
    {
        $variant->decrement('reserved_stock', $quantity);

        // Mettre à jour le stock agrégé du produit
        $product = $variant->product;
        $newAggStock = $product->variants()->sum('reserved_stock');
        $product->update(['reserved_stock' => $newAggStock]);

        $this->auditLog->execute(
            action: 'inventory.variant_stock_released',
            actor: null,
            target: $variant,
            after: [
                'reserved_stock' => $variant->reserved_stock,
                'quantity_released' => $quantity,
            ]
        );
    }
}

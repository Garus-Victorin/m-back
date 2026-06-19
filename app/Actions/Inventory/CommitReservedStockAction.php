<?php

namespace App\Actions\Inventory;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class CommitReservedStockAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function execute(Order $order): bool
    {
        if ($order->inventory_reserved_at === null) {
            Log::warning('Cannot commit stock for order that was never reserved', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        if ($order->inventory_committed_at !== null) {
            Log::warning('Stock already committed for this order', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        if ($order->inventory_released_at !== null) {
            Log::warning('Cannot commit released stock', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        $items = $order->items()->with(['product', 'variant'])->get();

        foreach ($items as $item) {
            if ($item->variant) {
                $this->commitVariantStock($item->variant, $item->quantity);
            } else {
                $this->commitProductStock($item->product, $item->quantity);
            }
        }

        $order->update([
            'inventory_committed_at' => now(),
        ]);

        $this->auditLog->execute(
            action: 'inventory.stock_committed',
            actor: null,
            target: $order,
            after: [
                'inventory_committed_at' => $order->inventory_committed_at,
            ]
        );

        Log::info('Reserved stock committed for order', [
            'order_id' => $order->id,
        ]);

        return true;
    }

    protected function commitProductStock(Product $product, int $quantity): void
    {
        // Déduire du stock réservé et du stock principal
        $product->decrement('reserved_stock', $quantity);
        $product->decrement('stock', $quantity);

        $this->auditLog->execute(
            action: 'inventory.product_stock_committed',
            actor: null,
            target: $product,
            after: [
                'stock' => $product->stock,
                'reserved_stock' => $product->reserved_stock,
                'quantity_committed' => $quantity,
            ]
        );
    }

    protected function commitVariantStock(ProductVariant $variant, int $quantity): void
    {
        // Déduire du stock réservé et du stock principal
        $variant->decrement('reserved_stock', $quantity);
        $variant->decrement('stock', $quantity);

        // Mettre à jour le stock agrégé du produit
        $product = $variant->product;
        $newAggStock = $product->variants()->sum('stock');
        $newAggReserved = $product->variants()->sum('reserved_stock');
        $product->update([
            'stock' => $newAggStock,
            'reserved_stock' => $newAggReserved,
        ]);

        $this->auditLog->execute(
            action: 'inventory.variant_stock_committed',
            actor: null,
            target: $variant,
            after: [
                'stock' => $variant->stock,
                'reserved_stock' => $variant->reserved_stock,
                'quantity_committed' => $quantity,
            ]
        );
    }
}

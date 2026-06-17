<?php

namespace App\Actions\Inventory;

use App\Models\Product;

class SyncProductInventoryFromVariantsAction
{
    public function execute(Product $product): Product
    {
        $totals = $product->variants()
            ->where('is_active', true)
            ->selectRaw('COALESCE(SUM(stock), 0) as total_stock, COALESCE(SUM(reserved_stock), 0) as total_reserved_stock')
            ->first();

        $product->forceFill([
            'stock' => (int) ($totals?->total_stock ?? 0),
            'reserved_stock' => (int) ($totals?->total_reserved_stock ?? 0),
        ])->save();

        return $product->fresh(['variants']);
    }
}

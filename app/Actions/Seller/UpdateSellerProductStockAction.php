<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateSellerProductStockAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function execute(User $user, Product $product, int $stock, ?Request $request = null): Product
    {
        if ($product->status === 'archived') {
            throw new ConflictHttpException('Archived products must be restored before updating stock.');
        }

        if ($product->variants()->exists()) {
            throw new ConflictHttpException('Stock must be updated through product variants once variants exist.');
        }

        $before = [
            'stock' => $product->stock,
            'reserved_stock' => $product->reserved_stock,
        ];

        if ($stock < $product->reserved_stock) {
            throw new ConflictHttpException('Stock cannot be lower than the reserved stock for this product.');
        }

        $product->update([
            'stock' => $stock,
        ]);

        $product = $product->fresh(['shop', 'category', 'reviewer', 'images', 'variants']);

        $this->auditLog->execute(
            action: 'seller.product.stock_updated',
            actor: $user,
            target: $product,
            before: $before,
            after: [
                'stock' => $product->stock,
                'reserved_stock' => $product->reserved_stock,
            ],
            request: $request,
        );

        return $product;
    }
}

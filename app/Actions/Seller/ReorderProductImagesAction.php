<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReorderProductImagesAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    /**
     * @param array<int, int> $imageIds
     */
    public function execute(User $user, Product $product, array $imageIds, ?Request $request = null): Product
    {
        if ($product->status === 'archived') {
            throw new ConflictHttpException('Archived products must be restored before reordering images.');
        }

        $before = $product->images()
            ->orderBy('position')
            ->get()
            ->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'position' => $image->position,
            ])
            ->all();

        DB::transaction(function () use ($product, $imageIds): void {
            foreach (array_values($imageIds) as $index => $imageId) {
                $product->images()->whereKey($imageId)->update([
                    'position' => $index + 1,
                ]);
            }
        });

        $product = $product->fresh(['shop', 'category', 'reviewer', 'images', 'variants']);

        $this->auditLog->execute(
            action: 'seller.product_images.reordered',
            actor: $user,
            target: $product,
            before: [
                'images' => $before,
            ],
            after: [
                'images' => $product->images->map(fn (ProductImage $image) => [
                    'id' => $image->id,
                    'position' => $image->position,
                ])->all(),
            ],
            request: $request,
        );

        return $product;
    }
}

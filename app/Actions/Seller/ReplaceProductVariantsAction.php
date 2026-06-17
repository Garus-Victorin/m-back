<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReplaceProductVariantsAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $variants
     */
    public function execute(User $user, Product $product, array $variants, ?Request $request = null): Product
    {
        if ($product->status === 'archived') {
            throw new ConflictHttpException('Archived products must be restored before updating variants.');
        }

        $beforeVariants = $product->variants()
            ->orderBy('position')
            ->get()
            ->map(fn (ProductVariant $variant) => $this->serializeVariant($variant))
            ->all();

        $beforeProduct = [
            'stock' => $product->stock,
            'reserved_stock' => $product->reserved_stock,
            'status' => $product->status,
            'moderation_status' => $product->moderation_status,
        ];

        DB::transaction(function () use ($product, $variants): void {
            $keptIds = [];

            foreach (array_values($variants) as $index => $payload) {
                $variant = null;

                if (! empty($payload['id'])) {
                    $variant = $product->variants()->whereKey($payload['id'])->first();
                }

                $variant = $variant ?? new ProductVariant(['product_id' => $product->id]);
                $variant->fill([
                    'attribute_name' => trim((string) $payload['attribute_name']),
                    'attribute_value' => trim((string) $payload['attribute_value']),
                    'sku' => $payload['sku'] ?: null,
                    'extra_price' => $payload['extra_price'] ?? 0,
                    'stock' => (int) $payload['stock'],
                    'is_active' => (bool) ($payload['is_active'] ?? true),
                    'position' => $index + 1,
                ]);
                $variant->save();

                $keptIds[] = $variant->id;
            }

            $product->variants()->when($keptIds !== [], fn ($query) => $query->whereKeyNot($keptIds), fn ($query) => $query)->delete();

            $product->update($this->syncProductInventoryPayload($product));
        });

        $product = $product->fresh(['shop', 'category', 'reviewer', 'images', 'variants']);

        $this->auditLog->execute(
            action: 'seller.product.variants_replaced',
            actor: $user,
            target: $product,
            before: [
                'product' => $beforeProduct,
                'variants' => $beforeVariants,
            ],
            after: [
                'product' => [
                    'stock' => $product->stock,
                    'reserved_stock' => $product->reserved_stock,
                    'status' => $product->status,
                    'moderation_status' => $product->moderation_status,
                ],
                'variants' => $product->variants->map(fn (ProductVariant $variant) => $this->serializeVariant($variant))->all(),
            ],
            request: $request,
        );

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    protected function syncProductInventoryPayload(Product $product): array
    {
        $totals = $product->variants()
            ->where('is_active', true)
            ->selectRaw('COALESCE(SUM(stock), 0) as total_stock, COALESCE(SUM(reserved_stock), 0) as total_reserved_stock')
            ->first();

        $attributes = [
            'stock' => (int) ($totals?->total_stock ?? 0),
            'reserved_stock' => (int) ($totals?->total_reserved_stock ?? 0),
        ];

        if (in_array($product->moderation_status, ['approved', 'suspended'], true)) {
            $attributes['status'] = 'draft';
            $attributes['moderation_status'] = 'draft';
            $attributes['submitted_for_review_at'] = null;
            $attributes['reviewed_by'] = null;
            $attributes['reviewed_at'] = null;
            $attributes['rejection_reason'] = null;
            $attributes['is_active'] = true;
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeVariant(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'attribute_name' => $variant->attribute_name,
            'attribute_value' => $variant->attribute_value,
            'sku' => $variant->sku,
            'extra_price' => $variant->extra_price,
            'stock' => $variant->stock,
            'reserved_stock' => $variant->reserved_stock,
            'is_active' => $variant->is_active,
            'position' => $variant->position,
        ];
    }
}

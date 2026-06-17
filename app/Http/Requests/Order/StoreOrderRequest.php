<?php

namespace App\Http\Requests\Order;

use App\Models\DeliveryAddress;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delivery_address_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $userId = $this->user()?->id;

                    if (! $userId || ! DeliveryAddress::query()->whereKey($value)->where('user_id', $userId)->exists()) {
                        $fail('The selected delivery address is invalid.');
                    }
                },
            ],
            'delivery_method' => ['sometimes', 'in:home,relay_point'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => [
                'sometimes',
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! filled($value)) {
                        return;
                    }

                    preg_match('/^items\.(\d+)\.product_variant_id$/', $attribute, $matches);
                    $index = isset($matches[1]) ? (int) $matches[1] : null;
                    $productId = $index !== null ? data_get($this->input(), "items.$index.product_id") : null;

                    if (! $productId || ! ProductVariant::query()->whereKey($value)->where('product_id', $productId)->exists()) {
                        $fail('The selected product variant is invalid.');
                    }
                },
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function ($validator): void {
            $items = $this->input('items', []);
            $shopId = null;
            $seenProductSignatures = [];

            foreach ($items as $index => $item) {
                $product = Product::query()
                    ->with('shop')
                    ->find($item['product_id'] ?? null);

                if (! $product) {
                    $validator->errors()->add("items.$index.product_id", 'The selected product is invalid.');
                    continue;
                }

                if (
                    $product->status !== 'published'
                    || $product->moderation_status !== 'approved'
                    || ! $product->is_active
                    || ! $product->shop
                    || $product->shop->status !== 'active'
                ) {
                    $validator->errors()->add("items.$index.product_id", 'Only active approved products can be ordered.');
                }

                if ($item['product_variant_id'] ?? null) {
                    $variant = ProductVariant::query()->find($item['product_variant_id']);

                    if (! $variant || ! $variant->is_active) {
                        $validator->errors()->add("items.$index.product_variant_id", 'Only active product variants can be ordered.');
                    }
                }

                if ($shopId === null) {
                    $shopId = $product->shop_id;
                } elseif ($shopId !== $product->shop_id) {
                    $validator->errors()->add('items', 'All order items must belong to the same shop.');
                }

                $signature = $product->id.'::'.($item['product_variant_id'] ?? 'none');

                if (isset($seenProductSignatures[$signature])) {
                    $validator->errors()->add("items.$index.product_id", 'Duplicate order lines for the same product selection are not allowed.');
                }

                $seenProductSignatures[$signature] = true;
            }
        }];
    }
}

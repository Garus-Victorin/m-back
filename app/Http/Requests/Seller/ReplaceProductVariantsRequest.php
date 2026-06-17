<?php

namespace App\Http\Requests\Seller;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceProductVariantsRequest extends FormRequest
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
            'variants' => ['required', 'array', 'max:100'],
            'variants.*.id' => [
                'sometimes',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    /** @var Product|null $product */
                    $product = $this->route('product');

                    if (! $product || ! ProductVariant::query()->whereKey($value)->where('product_id', $product->id)->exists()) {
                        $fail('The selected variant is invalid for this product.');
                    }
                },
            ],
            'variants.*.attribute_name' => ['required', 'string', 'max:100'],
            'variants.*.attribute_value' => ['required', 'string', 'max:150'],
            'variants.*.sku' => [
                'nullable',
                'string',
                'max:100',
                'distinct:ignore_case',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! filled($value)) {
                        return;
                    }

                    preg_match('/^variants\.(\d+)\.sku$/', $attribute, $matches);
                    $index = isset($matches[1]) ? (int) $matches[1] : null;
                    $variantId = $index !== null ? data_get($this->input(), "variants.$index.id") : null;

                    $query = ProductVariant::query()->where('sku', $value);

                    if ($variantId) {
                        $query->whereKeyNot($variantId);
                    }

                    if ($query->exists()) {
                        $fail('The variant sku has already been taken.');
                    }
                },
            ],
            'variants.*.extra_price' => ['sometimes', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function ($validator): void {
            $variants = $this->input('variants', []);
            $seen = [];

            foreach ($variants as $index => $variant) {
                $name = mb_strtolower(trim((string) ($variant['attribute_name'] ?? '')));
                $value = mb_strtolower(trim((string) ($variant['attribute_value'] ?? '')));

                if ($name === '' || $value === '') {
                    continue;
                }

                $signature = $name.'::'.$value;

                if (isset($seen[$signature])) {
                    $validator->errors()->add("variants.$index.attribute_value", 'Duplicate variant combinations are not allowed for the same product.');
                }

                $seen[$signature] = true;
            }
        }];
    }
}

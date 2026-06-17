<?php

namespace App\Http\Requests\Seller;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderProductImagesRequest extends FormRequest
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
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => [
                'required',
                'integer',
                'distinct',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    /** @var Product|null $product */
                    $product = $this->route('product');

                    if (! $product || ! ProductImage::query()->whereKey($value)->where('product_id', $product->id)->exists()) {
                        $fail('The selected image is invalid for this product.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function ($validator): void {
            /** @var Product|null $product */
            $product = $this->route('product');
            $imageIds = array_map('intval', $this->input('image_ids', []));

            if (! $product) {
                return;
            }

            $existingIds = $product->images()->pluck('id')->map(fn ($id) => (int) $id)->all();
            sort($existingIds);
            sort($imageIds);

            if ($existingIds !== $imageIds) {
                $validator->errors()->add('image_ids', 'The image_ids payload must contain each existing product image exactly once.');
            }
        }];
    }
}

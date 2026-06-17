<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $stock = fake()->numberBetween(0, 20);
        $attributeName = fake()->randomElement(['size', 'color']);
        $attributeValue = $attributeName === 'size'
            ? fake()->randomElement(['S', 'M', 'L', 'XL'])
            : fake()->randomElement(['Red', 'Blue', 'Black', 'Green']);

        return [
            'product_id' => Product::factory(),
            'attribute_name' => $attributeName,
            'attribute_value' => $attributeValue,
            'sku' => strtoupper(fake()->unique()->bothify('VAR-####??')),
            'extra_price' => fake()->randomFloat(2, 0, 5000),
            'stock' => $stock,
            'reserved_stock' => 0,
            'is_active' => true,
            'position' => fake()->numberBetween(1, 5),
        ];
    }
}

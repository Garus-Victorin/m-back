<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        $status = fake()->randomElement(['draft', 'published', 'archived']);
        $moderationStatus = match ($status) {
            'published' => 'approved',
            'archived' => 'draft',
            default => 'draft',
        };

        return [
            'shop_id' => Shop::factory(),
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'sku' => strtoupper(Str::random(10)),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 100, 50000),
            'stock' => fake()->numberBetween(0, 100),
            'reserved_stock' => 0,
            'status' => $status,
            'moderation_status' => $moderationStatus,
            'submitted_for_review_at' => $moderationStatus === 'approved' ? now()->subDay() : null,
            'reviewed_by' => null,
            'reviewed_at' => $moderationStatus === 'approved' ? now()->subHours(12) : null,
            'rejection_reason' => null,
            'archived_at' => $status === 'archived' ? now()->subDay() : null,
            'is_active' => $status !== 'archived',
        ];
    }
}

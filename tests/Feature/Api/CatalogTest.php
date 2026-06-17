<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

it('lists active categories', function () {
    $activeCategory = Category::factory()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);

    Category::factory()->create([
        'name' => 'Hidden Category',
        'slug' => 'hidden-category',
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/v1/categories');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.slug', $activeCategory->slug);
});

it('lists only published approved active products from active shops', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $activeShop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active', 'slug' => 'active-shop']);
    $inactiveShop = Shop::factory()->create(['status' => 'suspended', 'slug' => 'inactive-shop']);
    $category = Category::factory()->create(['slug' => 'phones']);

    $publishedProduct = Product::factory()->create([
        'shop_id' => $activeShop->id,
        'category_id' => $category->id,
        'name' => 'iPhone 15',
        'slug' => 'iphone-15',
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    Product::factory()->create([
        'shop_id' => $activeShop->id,
        'category_id' => $category->id,
        'slug' => 'draft-product',
        'status' => 'draft',
        'moderation_status' => 'draft',
    ]);

    Product::factory()->create([
        'shop_id' => $activeShop->id,
        'category_id' => $category->id,
        'slug' => 'pending-product',
        'status' => 'draft',
        'moderation_status' => 'pending_review',
    ]);

    Product::factory()->create([
        'shop_id' => $inactiveShop->id,
        'category_id' => $category->id,
        'slug' => 'suspended-shop-product',
        'status' => 'published',
        'moderation_status' => 'approved',
    ]);

    $response = $this->getJson('/api/v1/products?category=phones&shop=active-shop');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.slug', $publishedProduct->slug);
});

it('shows a published approved product by slug', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'slug' => 'published-product',
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/products/'.$product->slug)
        ->assertOk()
        ->assertJsonPath('data.product.slug', $product->slug)
        ->assertJsonPath('data.product.shop.slug', $shop->slug);
});

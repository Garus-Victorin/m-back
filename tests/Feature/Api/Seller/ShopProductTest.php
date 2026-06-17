<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function sellerAuthHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('test-suite')->plainTextToken,
    ];
}

function sellerProductUpload(string $originalName, string $mimeType = 'image/png'): UploadedFile
{
    $directory = storage_path('framework/testing/products');

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $path = $directory.'/'.uniqid('product_', true).'-'.$originalName;
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgN9sNQkAAAAASUVORK5CYII=');
    file_put_contents($path, $png);

    return new UploadedFile($path, $originalName, $mimeType, null, true);
}

it('allows a seller to create and update their shop', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);

    $createResponse = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/shops', [
            'name' => 'Marketify Store',
            'description' => 'Best store in town',
            'phone' => '+2250701010101',
            'email' => 'shop@example.com',
            'address' => 'Abidjan',
            'city' => 'Abidjan',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.shop.name', 'Marketify Store')
        ->assertJsonPath('data.shop.status', 'draft');

    $shop = Shop::query()->firstOrFail();

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/shops/'.$shop->id, [
            'name' => 'Marketify Mega Store',
            'city' => 'Yamoussoukro',
        ])
        ->assertOk()
        ->assertJsonPath('data.shop.name', 'Marketify Mega Store')
        ->assertJsonPath('data.shop.city', 'Yamoussoukro');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->getJson('/api/v1/seller/shops/me')
        ->assertOk()
        ->assertJsonPath('data.shop.slug', 'marketify-mega-store');
});

it('allows a seller to create update submit archive and restore their own product', function () {
    Storage::fake('local');

    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'verified',
    ]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $category = Category::factory()->create(['is_active' => true]);

    $createResponse = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products', [
            'category_id' => $category->id,
            'name' => 'Gaming Laptop',
            'sku' => 'LAPTOP-001',
            'short_description' => 'Powerful laptop',
            'description' => 'A powerful laptop for gamers.',
            'price' => 2500,
            'stock' => 7,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.product.shop_id', $shop->id)
        ->assertJsonPath('data.product.slug', 'gaming-laptop')
        ->assertJsonPath('data.product.status', 'draft')
        ->assertJsonPath('data.product.moderation_status', 'draft');

    $product = Product::query()->firstOrFail();

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id, [
            'name' => 'Gaming Laptop Pro',
            'price' => 2800,
            'stock' => 5,
        ])
        ->assertOk()
        ->assertJsonPath('data.product.slug', 'gaming-laptop-pro')
        ->assertJsonPath('data.product.price', '2800.00');

    $imageUpload = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/images', [
            'file' => sellerProductUpload('product.png'),
        ]);

    $imageUpload
        ->assertCreated()
        ->assertJsonPath('data.image.product_id', $product->id);

    $imageId = $imageUpload->json('data.image.id');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/submit-review')
        ->assertOk()
        ->assertJsonPath('data.product.moderation_status', 'pending_review');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'moderation_status' => 'pending_review',
        'status' => 'draft',
    ]);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/archive')
        ->assertOk()
        ->assertJsonPath('data.product.status', 'archived');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/restore')
        ->assertOk()
        ->assertJsonPath('data.product.status', 'draft')
        ->assertJsonPath('data.product.moderation_status', 'draft');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->get('/api/v1/seller/products/'.$product->id.'/images/'.$imageId.'/download')
        ->assertOk();

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->getJson('/api/v1/seller/products')
        ->assertOk()
        ->assertJsonCount(1, 'data.products');
});

it('allows a seller to retrieve product details and update stock for a simple product', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'stock' => 4,
        'reserved_stock' => 0,
        'status' => 'draft',
        'moderation_status' => 'draft',
    ]);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id.'/stock', [
            'stock' => 9,
        ])
        ->assertOk()
        ->assertJsonPath('data.product.stock', 9)
        ->assertJsonPath('data.product.reserved_stock', 0)
        ->assertJsonPath('data.product.available_stock', 9);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->getJson('/api/v1/seller/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.product.id', $product->id)
        ->assertJsonPath('data.product.stock', 9)
        ->assertJsonPath('data.product.available_stock', 9)
        ->assertJsonCount(0, 'data.product.variants');

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $seller->id,
        'action' => 'seller.product.stock_updated',
        'target_id' => $product->id,
    ]);
});

it('allows a seller to replace product variants and sync aggregate stock', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'stock' => 3,
        'reserved_stock' => 0,
        'status' => 'published',
        'moderation_status' => 'approved',
        'is_active' => true,
    ]);

    $response = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->putJson('/api/v1/seller/products/'.$product->id.'/variants', [
            'variants' => [
                [
                    'attribute_name' => 'size',
                    'attribute_value' => 'M',
                    'sku' => 'TEE-M',
                    'extra_price' => 500,
                    'stock' => 4,
                ],
                [
                    'attribute_name' => 'size',
                    'attribute_value' => 'L',
                    'sku' => 'TEE-L',
                    'extra_price' => 1000,
                    'stock' => 6,
                ],
            ],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.product.stock', 10)
        ->assertJsonPath('data.product.reserved_stock', 0)
        ->assertJsonPath('data.product.available_stock', 10)
        ->assertJsonPath('data.product.moderation_status', 'draft')
        ->assertJsonCount(2, 'data.product.variants');

    $this->assertDatabaseHas('product_variants', [
        'product_id' => $product->id,
        'attribute_name' => 'size',
        'attribute_value' => 'M',
        'sku' => 'TEE-M',
        'stock' => 4,
    ]);

    $this->assertDatabaseHas('product_variants', [
        'product_id' => $product->id,
        'attribute_name' => 'size',
        'attribute_value' => 'L',
        'sku' => 'TEE-L',
        'stock' => 6,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $seller->id,
        'action' => 'seller.product.variants_replaced',
        'target_id' => $product->id,
    ]);
});

it('forbids direct stock updates once a product has variants', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'stock' => 10,
        'reserved_stock' => 0,
        'status' => 'draft',
        'moderation_status' => 'draft',
        'is_active' => true,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'attribute_name' => 'size',
        'attribute_value' => 'M',
        'sku' => 'TEE-M-STOCK',
        'stock' => 10,
    ]);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id.'/stock', [
            'stock' => 2,
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Stock must be updated through product variants once variants exist.');
});

it('rejects duplicate variant combinations for the same product', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->putJson('/api/v1/seller/products/'.$product->id.'/variants', [
            'variants' => [
                [
                    'attribute_name' => 'size',
                    'attribute_value' => 'M',
                    'sku' => 'DUP-M-1',
                    'stock' => 4,
                ],
                [
                    'attribute_name' => 'size',
                    'attribute_value' => 'm',
                    'sku' => 'DUP-M-2',
                    'stock' => 2,
                ],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['variants.1.attribute_value']);
});

it('allows a seller to reorder product images and compacts positions after deletion', function () {
    Storage::fake('local');

    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $first = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/images', [
            'file' => sellerProductUpload('first.png'),
        ])
        ->assertCreated()
        ->json('data.image.id');

    $second = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/images', [
            'file' => sellerProductUpload('second.png'),
        ])
        ->assertCreated()
        ->json('data.image.id');

    $third = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/images', [
            'file' => sellerProductUpload('third.png'),
        ])
        ->assertCreated()
        ->json('data.image.id');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id.'/images/reorder', [
            'image_ids' => [$third, $first, $second],
        ])
        ->assertOk()
        ->assertJsonPath('data.product.images.0.id', $third)
        ->assertJsonPath('data.product.images.0.position', 1)
        ->assertJsonPath('data.product.images.1.id', $first)
        ->assertJsonPath('data.product.images.1.position', 2)
        ->assertJsonPath('data.product.images.2.id', $second)
        ->assertJsonPath('data.product.images.2.position', 3);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $seller->id,
        'action' => 'seller.product_images.reordered',
        'target_id' => $product->id,
    ]);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->deleteJson('/api/v1/seller/products/'.$product->id.'/images/'.$first)
        ->assertOk();

    $remainingImages = ProductImage::query()
        ->where('product_id', $product->id)
        ->orderBy('position')
        ->get(['id', 'position'])
        ->map(fn (ProductImage $image) => ['id' => $image->id, 'position' => $image->position])
        ->all();

    expect($remainingImages)->toBe([
        ['id' => $third, 'position' => 1],
        ['id' => $second, 'position' => 2],
    ]);
});

it('rejects invalid image reorder payloads when some product images are missing', function () {
    Storage::fake('local');

    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $first = $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/images', [
            'file' => sellerProductUpload('one.png'),
        ])
        ->assertCreated()
        ->json('data.image.id');

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->postJson('/api/v1/seller/products/'.$product->id.'/images', [
            'file' => sellerProductUpload('two.png'),
        ])
        ->assertCreated();

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id.'/images/reorder', [
            'image_ids' => [$first],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image_ids']);
});

it('forbids a seller from updating another sellers product', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $otherSeller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $otherShop = Shop::factory()->create(['user_id' => $otherSeller->id, 'status' => 'active']);
    $product = Product::factory()->create(['shop_id' => $otherShop->id]);

    $this
        ->withHeaders(sellerAuthHeaders($seller))
        ->patchJson('/api/v1/seller/products/'.$product->id, [
            'name' => 'Hijacked Product',
        ])
        ->assertForbidden();
});

it('forbids non sellers from creating shops', function () {
    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

    $this
        ->withHeaders(sellerAuthHeaders($customer))
        ->postJson('/api/v1/seller/shops', [
            'name' => 'Not Allowed Shop',
        ])
        ->assertForbidden();
});

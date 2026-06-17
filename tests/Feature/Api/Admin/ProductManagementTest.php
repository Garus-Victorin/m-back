<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('allows an admin to create list show review update and archive products', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true, 'kyc_status' => 'verified']);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $category = Category::factory()->create(['is_active' => true]);
    $headers = fn () => [
        'Authorization' => 'Bearer '.$admin->createToken('admin-product-suite')->plainTextToken,
        'X-Request-Id' => 'req-admin-product-001',
    ];

    $createResponse = $this
        ->withHeaders($headers())
        ->postJson('/api/v1/admin/products', [
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Admin Laptop',
            'sku' => 'ADMIN-LAPTOP-001',
            'short_description' => 'Managed by admin',
            'description' => 'Admin controlled product.',
            'price' => 1999.99,
            'stock' => 12,
            'status' => 'draft',
            'is_active' => true,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.product.name', 'Admin Laptop')
        ->assertJsonPath('data.product.slug', 'admin-laptop')
        ->assertJsonPath('data.product.moderation_status', 'draft');

    $product = Product::query()->firstOrFail();

    Storage::disk('local')->put('products/'.$seller->id.'/'.$product->id.'/cover.jpg', 'image-content');
    $image = ProductImage::create([
        'product_id' => $product->id,
        'disk' => 'local',
        'path' => 'products/'.$seller->id.'/'.$product->id.'/cover.jpg',
        'position' => 1,
        'original_name' => 'cover.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
    ]);

    $product->update([
        'moderation_status' => 'pending_review',
        'submitted_for_review_at' => now(),
    ]);

    $this
        ->withHeaders($headers())
        ->getJson('/api/v1/admin/products?shop_id='.$shop->id.'&moderation_status=pending_review')
        ->assertOk()
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.id', $product->id);

    $this
        ->withHeaders($headers())
        ->getJson('/api/v1/admin/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.product.id', $product->id)
        ->assertJsonPath('data.product.images.0.id', $image->id);

    $this
        ->withHeaders($headers())
        ->get('/api/v1/admin/products/'.$product->id.'/images/'.$image->id.'/download')
        ->assertOk();

    $this
        ->withHeaders($headers())
        ->patchJson('/api/v1/admin/products/'.$product->id.'/review', [
            'decision' => 'approved',
        ])
        ->assertOk()
        ->assertJsonPath('data.product.status', 'published')
        ->assertJsonPath('data.product.moderation_status', 'approved');

    $this
        ->withHeaders($headers())
        ->patchJson('/api/v1/admin/products/'.$product->id, [
            'name' => 'Admin Laptop Pro',
            'stock' => 4,
        ])
        ->assertOk()
        ->assertJsonPath('data.product.slug', 'admin-laptop-pro')
        ->assertJsonPath('data.product.status', 'published')
        ->assertJsonPath('data.product.moderation_status', 'approved');

    $this
        ->withHeaders($headers())
        ->deleteJson('/api/v1/admin/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.product.status', 'archived');

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'admin.product.reviewed',
        'target_type' => Product::class,
        'target_id' => $product->id,
        'request_id' => 'req-admin-product-001',
    ]);

    expect(AuditLog::query()->where('action', 'admin.product.reviewed')->count())->toBe(1);
});

it('forbids non admins from managing products as admin', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $product = Product::factory()->create();
    $headers = [
        'Authorization' => 'Bearer '.$seller->createToken('forbidden-admin-product')->plainTextToken,
    ];

    $this
        ->withHeaders($headers)
        ->getJson('/api/v1/admin/products')
        ->assertForbidden();

    $this
        ->withHeaders($headers)
        ->patchJson('/api/v1/admin/products/'.$product->id.'/review', [
            'decision' => 'approved',
        ])
        ->assertForbidden();
});

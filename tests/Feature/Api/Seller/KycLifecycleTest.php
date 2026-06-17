<?php

use App\Models\SellerKycSubmission;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function sellerLifecycleHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('seller-lifecycle-suite')->plainTextToken,
    ];
}

function sellerLifecycleUpload(string $originalName, string $mimeType = 'application/pdf'): UploadedFile
{
    $directory = storage_path('framework/testing/kyc');

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $path = $directory.'/'.uniqid('upload_', true).'-'.$originalName;

    file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

    return new UploadedFile(
        $path,
        $originalName,
        $mimeType,
        null,
        true,
    );
}

it('allows a seller to upload submit retrieve and download their kyc submission', function () {
    Storage::fake('local');

    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'rejected',
    ]);

    Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'draft',
    ]);

    $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->getJson('/api/v1/seller/kyc')
        ->assertOk()
        ->assertJsonPath('data.kyc_submission', null);

    $frontUpload = $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->postJson('/api/v1/seller/kyc/uploads/front', [
            'file' => sellerLifecycleUpload('front.pdf'),
        ]);

    $frontUpload
        ->assertCreated()
        ->assertJsonPath('data.file.disk', 'local');

    $frontPath = $frontUpload->json('data.file.path');

    $backUpload = $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->postJson('/api/v1/seller/kyc/uploads/back', [
            'file' => sellerLifecycleUpload('back.pdf'),
        ]);

    $backUpload->assertCreated();
    $backPath = $backUpload->json('data.file.path');

    Storage::disk('local')->assertExists($frontPath);
    Storage::disk('local')->assertExists($backPath);

    $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->postJson('/api/v1/seller/kyc', [
            'document_type' => 'national_id',
            'document_number' => 'ID-123456',
            'document_front_path' => $frontPath,
            'document_back_path' => $backPath,
            'mobile_money_provider' => 'MTN',
            'mobile_money_number' => '+22997000000',
            'notes' => 'Seller KYC submission',
        ])
        ->assertCreated()
        ->assertJsonPath('data.kyc_submission.status', 'pending')
        ->assertJsonPath('data.kyc_submission.document_type', 'national_id');

    $this
        ->withHeaders(sellerLifecycleHeaders($seller->fresh()))
        ->getJson('/api/v1/seller/kyc')
        ->assertOk()
        ->assertJsonPath('data.kyc_submission.mobile_money_provider', 'MTN')
        ->assertJsonPath('data.kyc_submission.document_front_path', $frontPath)
        ->assertJsonPath('data.kyc_submission.document_front_download_url', '/api/v1/seller/kyc/files/front');

    $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->get('/api/v1/seller/kyc/files/front')
        ->assertOk();

    $seller->refresh();

    expect($seller->kyc_status)->toBe('pending');
    expect($seller->kyc_document_url)->toBe($frontPath);
    expect(SellerKycSubmission::query()->count())->toBe(1);
});

it('rejects kyc submission when the file path does not belong to the current seller', function () {
    Storage::fake('local');

    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
    ]);

    Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'draft',
    ]);

    Storage::disk('local')->put('kyc/999/foreign_front.jpg', 'fake-file');

    $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->postJson('/api/v1/seller/kyc', [
            'document_type' => 'national_id',
            'document_front_path' => 'kyc/999/foreign_front.jpg',
            'mobile_money_provider' => 'MTN',
            'mobile_money_number' => '+22997000000',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['document_front_path']);
});

it('allows a seller to submit their shop for review after kyc submission', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'pending',
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'name' => 'Draft Seller Shop',
        'phone' => '+22997000000',
        'email' => 'seller@example.com',
        'address' => 'Cotonou',
        'city' => 'Cotonou',
        'status' => 'draft',
        'submitted_at' => null,
    ]);

    SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'pending',
    ]);

    $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->postJson('/api/v1/seller/shops/submit-review')
        ->assertOk()
        ->assertJsonPath('data.shop.id', $shop->id)
        ->assertJsonPath('data.shop.status', 'pending');

    $this->assertDatabaseHas('shops', [
        'id' => $shop->id,
        'status' => 'pending',
    ]);

    expect($shop->fresh()->submitted_at)->not->toBeNull();
});

it('rejects shop review submission when kyc is missing', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
    ]);

    Shop::factory()->create([
        'user_id' => $seller->id,
        'name' => 'Draft Seller Shop',
        'phone' => '+22997000000',
        'email' => 'seller@example.com',
        'address' => 'Cotonou',
        'city' => 'Cotonou',
        'status' => 'draft',
    ]);

    $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->postJson('/api/v1/seller/shops/submit-review')
        ->assertStatus(422)
        ->assertJsonPath('message', 'A KYC submission is required before requesting shop review.');
});

it('rejects invalid legacy product publication status from a seller payload', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'draft',
    ]);

    $category = \App\Models\Category::factory()->create(['is_active' => true]);

    $this
        ->withHeaders(sellerLifecycleHeaders($seller))
        ->postJson('/api/v1/seller/products', [
            'category_id' => $category->id,
            'name' => 'Blocked Product',
            'sku' => 'BLOCKED-001',
            'short_description' => 'Blocked product',
            'description' => 'Cannot be self-published by seller',
            'price' => 1000,
            'stock' => 4,
            'status' => 'published',
            'is_active' => true,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    expect($shop->fresh()->status)->toBe('draft');
});

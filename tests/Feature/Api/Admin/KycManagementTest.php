<?php

use App\Models\AuditLog;
use App\Models\SellerKycSubmission;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function adminKycHeaders(User $user, string $requestId = 'req-admin-kyc-001'): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('admin-kyc-suite')->plainTextToken,
        'X-Request-Id' => $requestId,
    ];
}

it('allows an admin to list inspect download and verify seller kyc submissions', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true, 'kyc_status' => 'pending']);
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'name' => 'KYC Shop',
        'status' => 'pending',
    ]);

    Storage::disk('local')->put('kyc/'.$seller->id.'/front.jpg', 'front-content');
    Storage::disk('local')->put('kyc/'.$seller->id.'/back.jpg', 'back-content');

    $submission = SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'pending',
        'document_number' => 'ID-KYC-001',
        'document_front_path' => 'kyc/'.$seller->id.'/front.jpg',
        'document_back_path' => 'kyc/'.$seller->id.'/back.jpg',
    ]);

    $this
        ->withHeaders(adminKycHeaders($admin))
        ->getJson('/api/v1/admin/kyc-submissions?status=pending&search=KYC Shop')
        ->assertOk()
        ->assertJsonCount(1, 'data.kyc_submissions')
        ->assertJsonPath('data.kyc_submissions.0.id', $submission->id)
        ->assertJsonPath('data.kyc_submissions.0.shop.name', 'KYC Shop');

    $this
        ->withHeaders(adminKycHeaders($admin))
        ->getJson('/api/v1/admin/kyc-submissions/'.$submission->id)
        ->assertOk()
        ->assertJsonPath('data.kyc_submission.user.id', $seller->id)
        ->assertJsonPath('data.kyc_submission.document_front_download_url', '/api/v1/admin/kyc-submissions/'.$submission->id.'/files/front');

    $this
        ->withHeaders(adminKycHeaders($admin))
        ->get('/api/v1/admin/kyc-submissions/'.$submission->id.'/files/front')
        ->assertOk();

    $this
        ->withHeaders(adminKycHeaders($admin, 'req-admin-kyc-verify'))
        ->patchJson('/api/v1/admin/kyc-submissions/'.$submission->id, [
            'status' => 'verified',
        ])
        ->assertOk()
        ->assertJsonPath('data.kyc_submission.status', 'verified')
        ->assertJsonPath('data.kyc_submission.reviewed_by', $admin->id);

    $this->assertDatabaseHas('seller_kyc_submissions', [
        'id' => $submission->id,
        'status' => 'verified',
        'reviewed_by' => $admin->id,
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $seller->id,
        'kyc_status' => 'verified',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'admin.seller_kyc.reviewed',
        'target_type' => SellerKycSubmission::class,
        'target_id' => $submission->id,
        'request_id' => 'req-admin-kyc-verify',
    ]);

    expect(AuditLog::query()->where('action', 'admin.seller_kyc.reviewed')->count())->toBe(1);
});

it('rejects invalid admin kyc review flows', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true, 'kyc_status' => 'pending']);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'pending']);

    $submission = SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'pending',
    ]);

    $this
        ->withHeaders(adminKycHeaders($admin))
        ->patchJson('/api/v1/admin/kyc-submissions/'.$submission->id, [
            'status' => 'rejected',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rejection_reason']);

    $submission->update([
        'status' => 'verified',
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
    ]);

    $this
        ->withHeaders(adminKycHeaders($admin))
        ->patchJson('/api/v1/admin/kyc-submissions/'.$submission->id, [
            'status' => 'rejected',
            'rejection_reason' => 'Late rejection should fail',
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Only pending KYC submissions can be reviewed.');
});

it('forbids non admins from managing seller kyc submissions', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true, 'kyc_status' => 'pending']);

    $this
        ->withHeaders(adminKycHeaders($seller))
        ->getJson('/api/v1/admin/kyc-submissions')
        ->assertForbidden();
});

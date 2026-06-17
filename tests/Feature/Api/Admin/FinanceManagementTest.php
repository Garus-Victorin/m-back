<?php

use App\Models\AuditLog;
use App\Models\SellerWithdrawalRequest;
use App\Models\Shop;
use App\Models\User;

function adminFinanceHeaders(User $user, string $requestId = 'req-admin-finance-001'): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('admin-finance-suite')->plainTextToken,
        'X-Request-Id' => $requestId,
    ];
}

it('allows an admin to list inspect and process seller withdrawals', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true, 'kyc_status' => 'verified']);
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'name' => 'Finance Shop',
        'status' => 'active',
    ]);

    $withdrawal = SellerWithdrawalRequest::factory()->create([
        'shop_id' => $shop->id,
        'user_id' => $seller->id,
        'status' => 'pending',
        'idempotency_key' => 'withdraw-admin-001',
        'mobile_money_number' => '+22997000000',
    ]);

    $this
        ->withHeaders(adminFinanceHeaders($admin))
        ->getJson('/api/v1/admin/finance/withdrawals?status=pending&search=Finance Shop')
        ->assertOk()
        ->assertJsonCount(1, 'data.withdrawals')
        ->assertJsonPath('data.withdrawals.0.id', $withdrawal->id)
        ->assertJsonPath('data.withdrawals.0.shop.name', 'Finance Shop');

    $this
        ->withHeaders(adminFinanceHeaders($admin))
        ->getJson('/api/v1/admin/finance/withdrawals/'.$withdrawal->id)
        ->assertOk()
        ->assertJsonPath('data.withdrawal.user.id', $seller->id);

    $this
        ->withHeaders(adminFinanceHeaders($admin, 'req-admin-finance-processing'))
        ->patchJson('/api/v1/admin/finance/withdrawals/'.$withdrawal->id, [
            'status' => 'processing',
            'provider_reference' => 'provider-start-001',
        ])
        ->assertOk()
        ->assertJsonPath('data.withdrawal.status', 'processing')
        ->assertJsonPath('data.withdrawal.provider_reference', 'provider-start-001');

    $this
        ->withHeaders(adminFinanceHeaders($admin, 'req-admin-finance-paid'))
        ->patchJson('/api/v1/admin/finance/withdrawals/'.$withdrawal->id, [
            'status' => 'paid',
            'provider_reference' => 'provider-paid-001',
        ])
        ->assertOk()
        ->assertJsonPath('data.withdrawal.status', 'paid')
        ->assertJsonPath('data.withdrawal.processed_by', $admin->id)
        ->assertJsonPath('data.withdrawal.provider_reference', 'provider-paid-001');

    $this->assertDatabaseHas('seller_withdrawal_requests', [
        'id' => $withdrawal->id,
        'status' => 'paid',
        'processed_by' => $admin->id,
        'provider_reference' => 'provider-paid-001',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'admin.seller_withdrawal.updated',
        'target_type' => SellerWithdrawalRequest::class,
        'target_id' => $withdrawal->id,
        'request_id' => 'req-admin-finance-paid',
    ]);

    expect(AuditLog::query()->where('action', 'admin.seller_withdrawal.updated')->count())->toBe(2);
});

it('rejects invalid admin withdrawal transitions', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $withdrawal = SellerWithdrawalRequest::factory()->create([
        'shop_id' => $shop->id,
        'user_id' => $seller->id,
        'status' => 'pending',
    ]);

    $this
        ->withHeaders(adminFinanceHeaders($admin))
        ->patchJson('/api/v1/admin/finance/withdrawals/'.$withdrawal->id, [
            'status' => 'paid',
            'provider_reference' => 'should-fail',
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Invalid seller withdrawal status transition.');
});

it('forbids non admins from managing seller withdrawals', function () {
    $seller = User::factory()->create(['role' => 'seller', 'is_active' => true]);

    $this
        ->withHeaders(adminFinanceHeaders($seller))
        ->getJson('/api/v1/admin/finance/withdrawals')
        ->assertForbidden();
});

<?php

use App\Models\AuditLog;
use App\Models\SellerKycSubmission;
use App\Models\Shop;
use App\Models\User;

function sellerSettingsHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('seller-settings-suite')->plainTextToken,
        'X-Request-Id' => 'req-seller-settings-001',
    ];
}

it('allows a seller to read and update payout settings', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    $this
        ->withHeaders(sellerSettingsHeaders($seller))
        ->getJson('/api/v1/seller/settings')
        ->assertOk()
        ->assertJsonPath('data.settings.shop_id', $shop->id)
        ->assertJsonPath('data.settings.has_complete_payout_settings', false);

    $this
        ->withHeaders(sellerSettingsHeaders($seller))
        ->patchJson('/api/v1/seller/settings', [
            'payout_beneficiary_name' => 'Marketify Seller',
            'payout_mobile_money_provider' => 'MTN',
            'payout_mobile_money_number' => '+22997000000',
            'payouts_enabled' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.settings.payout_beneficiary_name', 'Marketify Seller')
        ->assertJsonPath('data.settings.payout_mobile_money_provider', 'MTN')
        ->assertJsonPath('data.settings.has_complete_payout_settings', true);

    $this->assertDatabaseHas('shops', [
        'id' => $shop->id,
        'payout_beneficiary_name' => 'Marketify Seller',
        'payout_mobile_money_provider' => 'MTN',
        'payout_mobile_money_number' => '+22997000000',
        'payouts_enabled' => true,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $seller->id,
        'action' => 'seller.settings.updated',
        'target_type' => Shop::class,
        'target_id' => $shop->id,
        'request_id' => 'req-seller-settings-001',
    ]);

    expect(AuditLog::query()->where('action', 'seller.settings.updated')->count())->toBe(1);
});

it('uses dedicated payout settings in seller withdrawals when available', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'verified',
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'payout_beneficiary_name' => 'Dedicated Payout',
        'payout_mobile_money_provider' => 'MOOV',
        'payout_mobile_money_number' => '+22996000000',
        'payouts_enabled' => true,
    ]);

    SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'verified',
        'mobile_money_provider' => 'MTN',
        'mobile_money_number' => '+22997000000',
    ]);

    \App\Models\Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'delivered',
        'seller_amount' => 25.00,
    ]);

    $this
        ->withHeaders(array_merge(sellerSettingsHeaders($seller), ['Idempotency-Key' => 'withdraw-settings-001']))
        ->postJson('/api/v1/seller/finance/withdrawals', [
            'amount_cents' => 1000,
        ])
        ->assertCreated()
        ->assertJsonPath('data.withdrawal.mobile_money_provider', 'MOOV')
        ->assertJsonPath('data.withdrawal.mobile_money_number', '+22996000000');
});

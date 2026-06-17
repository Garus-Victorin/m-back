<?php

use App\Models\Order;
use App\Models\SellerKycSubmission;
use App\Models\SellerWithdrawalRequest;
use App\Models\Shop;
use App\Models\User;

function sellerFinanceHeaders(User $user, ?string $idempotencyKey = null): array
{
    $headers = [
        'Authorization' => 'Bearer '.$user->createToken('seller-finance-suite')->plainTextToken,
    ];

    if ($idempotencyKey !== null) {
        $headers['Idempotency-Key'] = $idempotencyKey;
    }

    return $headers;
}

it('returns seller finance summary and withdrawal history', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'verified',
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'verified',
        'mobile_money_provider' => 'MTN',
        'mobile_money_number' => '+22997000000',
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'delivered',
        'seller_amount' => 20.00,
        'platform_commission' => 2.00,
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'paid',
        'seller_amount' => 5.00,
        'platform_commission' => 1.00,
    ]);

    SellerWithdrawalRequest::factory()->create([
        'shop_id' => $shop->id,
        'user_id' => $seller->id,
        'amount_cents' => 500,
        'status' => 'paid',
    ]);

    SellerWithdrawalRequest::factory()->create([
        'shop_id' => $shop->id,
        'user_id' => $seller->id,
        'amount_cents' => 300,
        'status' => 'pending',
    ]);

    $this
        ->withHeaders(sellerFinanceHeaders($seller))
        ->getJson('/api/v1/seller/finance/summary')
        ->assertOk()
        ->assertJsonPath('data.summary.currency', 'XOF')
        ->assertJsonPath('data.summary.available_balance_cents', 1200)
        ->assertJsonPath('data.summary.pending_balance_cents', 500)
        ->assertJsonPath('data.summary.total_earned_cents', 2500)
        ->assertJsonPath('data.summary.total_withdrawn_cents', 500)
        ->assertJsonPath('data.summary.total_commissions_cents', 300)
        ->assertJsonPath('data.summary.pending_withdrawals_cents', 300)
        ->assertJsonPath('data.summary.min_withdrawal_cents', 500);

    $this
        ->withHeaders(sellerFinanceHeaders($seller))
        ->getJson('/api/v1/seller/finance/withdrawals')
        ->assertOk()
        ->assertJsonCount(2, 'data.withdrawals');
});

it('creates an idempotent seller withdrawal request', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'verified',
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'verified',
        'mobile_money_provider' => 'MOOV',
        'mobile_money_number' => '+22996000000',
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'delivered',
        'seller_amount' => 30.00,
        'platform_commission' => 3.00,
    ]);

    $headers = sellerFinanceHeaders($seller, 'withdraw-001');

    $firstResponse = $this
        ->withHeaders($headers)
        ->postJson('/api/v1/seller/finance/withdrawals', [
            'amount_cents' => 1500,
        ]);

    $firstResponse
        ->assertCreated()
        ->assertJsonPath('data.withdrawal.amount_cents', 1500)
        ->assertJsonPath('data.withdrawal.mobile_money_provider', 'MOOV')
        ->assertJsonPath('data.withdrawal.status', 'pending');

    $firstId = $firstResponse->json('data.withdrawal.id');

    $secondResponse = $this
        ->withHeaders($headers)
        ->postJson('/api/v1/seller/finance/withdrawals', [
            'amount_cents' => 1500,
        ]);

    $secondResponse
        ->assertCreated()
        ->assertJsonPath('data.withdrawal.id', $firstId);

    expect(SellerWithdrawalRequest::query()->count())->toBe(1);
});

it('rejects a withdrawal without idempotency key or sufficient eligibility', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'verified',
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'verified',
        'mobile_money_provider' => 'MTN',
        'mobile_money_number' => '+22997000000',
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'delivered',
        'seller_amount' => 10.00,
    ]);

    $this
        ->withHeaders(sellerFinanceHeaders($seller))
        ->postJson('/api/v1/seller/finance/withdrawals', [
            'amount_cents' => 500,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['idempotency_key']);

    $this
        ->withHeaders(sellerFinanceHeaders($seller, 'withdraw-002'))
        ->postJson('/api/v1/seller/finance/withdrawals', [
            'amount_cents' => 1500,
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Insufficient available balance for this withdrawal request.');
});

it('rejects a withdrawal for sellers without verified kyc', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
        'kyc_status' => 'pending',
    ]);

    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    SellerKycSubmission::factory()->create([
        'user_id' => $seller->id,
        'shop_id' => $shop->id,
        'status' => 'pending',
        'mobile_money_provider' => 'MTN',
        'mobile_money_number' => '+22997000000',
    ]);

    Order::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'delivered',
        'seller_amount' => 20.00,
    ]);

    $this
        ->withHeaders(sellerFinanceHeaders($seller, 'withdraw-003'))
        ->postJson('/api/v1/seller/finance/withdrawals', [
            'amount_cents' => 500,
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'KYC verification is required before requesting a withdrawal.');
});

<?php

namespace Database\Factories;

use App\Models\SellerWithdrawalRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SellerWithdrawalRequest>
 */
class SellerWithdrawalRequestFactory extends Factory
{
    protected $model = SellerWithdrawalRequest::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'user_id' => User::factory()->state([
                'role' => 'seller',
                'is_active' => true,
                'kyc_status' => 'verified',
            ]),
            'amount_cents' => fake()->numberBetween(500, 500000),
            'currency' => 'XOF',
            'mobile_money_provider' => fake()->randomElement(['MTN', 'MOOV']),
            'mobile_money_number' => '+22997000000',
            'status' => fake()->randomElement(['pending', 'processing', 'paid', 'failed', 'rejected']),
            'idempotency_key' => 'idem-'.Str::lower(Str::random(16)),
            'processed_by' => null,
            'processed_at' => null,
            'failure_reason' => null,
            'provider_reference' => null,
        ];
    }
}

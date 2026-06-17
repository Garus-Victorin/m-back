<?php

namespace Database\Factories;

use App\Models\SellerKycSubmission;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerKycSubmission>
 */
class SellerKycSubmissionFactory extends Factory
{
    protected $model = SellerKycSubmission::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => 'seller',
                'is_active' => true,
            ]),
            'shop_id' => Shop::factory(),
            'document_type' => fake()->randomElement(['national_id', 'passport', 'business_registration']),
            'document_number' => strtoupper(fake()->bothify('DOC-####')),
            'document_front_path' => 'kyc/front-'.fake()->uuid().'.jpg',
            'document_back_path' => 'kyc/back-'.fake()->uuid().'.jpg',
            'mobile_money_provider' => fake()->randomElement(['MTN', 'MOOV']),
            'mobile_money_number' => '+22997000000',
            'notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'verified', 'rejected']),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }
}

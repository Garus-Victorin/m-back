<?php

namespace Database\Factories;

use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 50000);
        $deliveryFee = fake()->randomFloat(2, 0, 5000);
        $total = $subtotal + $deliveryFee;
        $platformCommission = round($subtotal * 0.08, 2);
        $sellerAmount = round($subtotal - $platformCommission, 2);

        return [
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'customer_id' => User::factory()->state([
                'role' => 'customer',
                'is_active' => true,
            ]),
            'shop_id' => Shop::factory(),
            'delivery_address_id' => DeliveryAddress::factory(),
            'delivery_method' => fake()->randomElement(['home', 'relay_point']),
            'otp_code' => fake()->optional()->numerify('######'),
            'status' => fake()->randomElement(['pending', 'paid', 'preparing', 'ready_for_pickup', 'picked_up', 'in_delivery', 'delivered', 'cancelled']),
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'platform_commission' => $platformCommission,
            'seller_amount' => $sellerAmount,
            'delivery_amount' => fake()->randomFloat(2, 0, 5000),
            'payment_details' => ['channel' => 'MTN'],
            'paid_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

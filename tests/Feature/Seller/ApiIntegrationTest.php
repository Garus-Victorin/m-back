<?php

namespace Tests\Feature\Seller;

use App\Models\Order;
use App\Models\Product;
use App\Models\SellerWithdrawalRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Log::shouldReceive("info")->andReturnNull();
        Log::shouldReceive("error")->andReturnNull();
    }

    public function test_seller_me_endpoint(): void
    {
        $user = User::factory()->create([
            "role" => "seller",
            "is_active" => true,
            "kyc_status" => "verified",
        ]);

        $shop = Shop::factory()->create([
            "user_id" => $user->id,
            "status" => "active",
        ]);

        $token = $user->createToken("test-token")->plainTextToken;

        $response = $this->withHeader("Authorization", "Bearer {$token}")->get(
            "/api/v1/seller/me",
        );

        $response
            ->assertStatus(200)
            ->assertJson([
                "success" => true,
            ])
            ->assertJsonStructure([
                "data" => ["seller", "shop", "capabilities"],
            ]);
    }

    public function test_product_image_limit_enforcement(): void
    {
        $user = User::factory()->create([
            "role" => "seller",
            "is_active" => true,
        ]);

        $shop = Shop::factory()->create([
            "user_id" => $user->id,
            "status" => "active",
        ]);

        $product = Product::factory()->create([
            "shop_id" => $shop->id,
        ]);

        // Créer 10 images (limite maximale)
        for ($i = 0; $i < 10; $i++) {
            $product->images()->create([
                "path" => "test{$i}.jpg",
                "disk" => "public",
                "position" => $i,
            ]);
        }

        $token = $user->createToken("test-token")->plainTextToken;

        // Tenter d'ajouter une 11ème image - utiliser un fichier uploadé valide
        $file = \Illuminate\Http\UploadedFile::fake()->image("test.jpg");

        $response = $this->withHeader("Authorization", "Bearer {$token}")->post(
            "/api/v1/seller/products/{$product->id}/images",
            [
                "file" => $file,
            ],
        );

        $response->assertStatus(409)->assertJson([
            "success" => false,
            "code" => "PRODUCT_IMAGE_LIMIT_REACHED",
        ]);
    }

    public function test_order_cancellation_request(): void
    {
        $user = User::factory()->create([
            "role" => "seller",
            "is_active" => true,
        ]);

        $shop = Shop::factory()->create([
            "user_id" => $user->id,
            "status" => "active",
        ]);

        $order = Order::factory()->create([
            "shop_id" => $shop->id,
            "status" => "pending",
        ]);

        $token = $user->createToken("test-token")->plainTextToken;

        $response = $this->withHeader("Authorization", "Bearer {$token}")->post(
            "/api/v1/seller/orders/{$order->id}/cancel-request",
            [
                "reason" => "out_of_stock",
                "details" => "Product is no longer available",
            ],
        );

        $response->assertStatus(200)->assertJson([
            "success" => true,
            "message" => "Order cancellation requested successfully",
        ]);

        $this->assertDatabaseHas("orders", [
            "id" => $order->id,
            "status" => "cancel_requested",
            "cancel_reason" => "out_of_stock",
        ]);
    }

    public function test_packing_slip_generation(): void
    {
        $user = User::factory()->create([
            "role" => "seller",
            "is_active" => true,
        ]);

        $shop = Shop::factory()->create([
            "user_id" => $user->id,
            "status" => "active",
        ]);

        $order = Order::factory()->create([
            "shop_id" => $shop->id,
            "status" => "paid",
        ]);

        $token = $user->createToken("test-token")->plainTextToken;

        $response = $this->withHeader("Authorization", "Bearer {$token}")->get(
            "/api/v1/seller/orders/{$order->id}/packing-slip",
        );

        $response->assertStatus(200)->assertJsonStructure([
            "success",
            "message",
            "data" => [
                "packing_slip" => [
                    "order",
                    "shop",
                    "customer",
                    "delivery_address",
                    "items",
                    "totals",
                ],
            ],
        ]);
    }

    public function test_withdrawal_processing(): void
    {
        $user = User::factory()->create([
            "role" => "seller",
            "is_active" => true,
            "kyc_status" => "verified",
        ]);

        $shop = Shop::factory()->create([
            "user_id" => $user->id,
            "status" => "active",
            "payouts_enabled" => true,
            "payout_mobile_money_provider" => "MTN",
            "payout_mobile_money_number" => "22912345678",
        ]);

        $withdrawal = SellerWithdrawalRequest::factory()->create([
            "user_id" => $user->id,
            "shop_id" => $shop->id,
            "amount_cents" => 10000,
            "status" => "pending",
            "mobile_money_provider" => "MTN",
            "mobile_money_number" => "22912345678",
        ]);

        $token = $user->createToken("test-token")->plainTextToken;

        $response = $this->withHeader("Authorization", "Bearer {$token}")->post(
            "/api/v1/seller/finance/withdrawals/{$withdrawal->id}/process",
        );

        $response->assertStatus(200)->assertJson([
            "success" => true,
            "message" => "Withdrawal processing job dispatched",
        ]);
    }

    public function test_transactions_endpoint(): void
    {
        $user = User::factory()->create([
            "role" => "seller",
            "is_active" => true,
        ]);

        $shop = Shop::factory()->create([
            "user_id" => $user->id,
            "status" => "active",
        ]);

        $token = $user->createToken("test-token")->plainTextToken;

        $response = $this->withHeader("Authorization", "Bearer {$token}")->get(
            "/api/v1/seller/finance/transactions",
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                "success",
                "message",
                "data" => [
                    "transactions",
                    "summary" => [
                        "total_transactions",
                        "total_sales",
                        "total_commissions",
                        "total_withdrawals",
                        "net_balance",
                    ],
                ],
            ]);
    }

    public function test_notifications_endpoint(): void
    {
        $user = User::factory()->create([
            "role" => "seller",
            "is_active" => true,
        ]);

        $shop = Shop::factory()->create([
            "user_id" => $user->id,
            "status" => "active",
        ]);

        $token = $user->createToken("test-token")->plainTextToken;

        $response = $this->withHeader("Authorization", "Bearer {$token}")->get(
            "/api/v1/seller/notifications",
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                "success",
                "message",
                "data" => [
                    "notifications",
                    "pagination",
                    "summary" => ["unread_count"],
                ],
            ]);
    }
}

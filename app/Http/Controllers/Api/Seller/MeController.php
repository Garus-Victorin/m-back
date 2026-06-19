<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Seller\SellerMeResource;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $shop = $this->sellerShop($user);

            return response()->json([
                "success" => true,
                "message" => "Seller information retrieved successfully.",
                "data" => [
                    "seller" => SellerMeResource::make($user),
                    "shop" => $shop,
                    "capabilities" => $this->getSellerCapabilities(
                        $user,
                        $shop,
                    ),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Failed to retrieve seller information: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }

    protected function getSellerCapabilities(User $user, Shop $shop): array
    {
        $capabilities = [
            "can_create_products" => true,
            "can_manage_orders" => true,
            "can_request_withdrawals" =>
                $shop->status === "active" && $user->kyc_status === "verified",
            "can_upload_media" => true,
            "can_manage_shop_settings" => true,
            "can_view_finance" => true,
            "can_view_dashboard" => true,
        ];

        // Capacités basées sur le statut du shop
        if ($shop->status !== "active") {
            $capabilities["can_request_withdrawals"] = false;
            $capabilities["can_create_products"] = in_array($shop->status, [
                "active",
                "pending",
            ]);
        }

        // Capacités basées sur le KYC
        if ($user->kyc_status !== "verified") {
            $capabilities["can_request_withdrawals"] = false;
        }

        return $capabilities;
    }
}

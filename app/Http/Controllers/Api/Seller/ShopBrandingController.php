<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Actions\Seller\StoreShopBrandingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreShopLogoRequest;
use App\Http\Resources\Seller\ShopBrandingResource;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopBrandingController extends Controller
{
    public function uploadLogo(
        StoreShopLogoRequest $request,
        StoreShopBrandingAction $action,
        RecordAuditLogAction $auditLog
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $file = $request->file('logo');
        $result = $action->execute($shop, $file, 'logo');

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to upload logo.',
            ], 422);
        }

        $auditLog->execute(
            action: 'seller.shop.logo_uploaded',
            actor: $user,
            target: $shop,
            after: ['logo_url' => $result['url']],
            request: $request
        );

        Log::info('Shop logo uploaded', [
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop logo uploaded successfully.',
            'data' => [
                'branding' => ShopBrandingResource::make($shop),
            ],
        ]);
    }

    public function uploadBanner(
        Request $request,
        StoreShopBrandingAction $action,
        RecordAuditLogAction $auditLog
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $validated = $request->validate([
            'banner' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // 5MB
                'dimensions:min_width=1200,min_height=400',
            ],
        ]);

        $file = $request->file('banner');
        $result = $action->execute($shop, $file, 'banner');

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to upload banner.',
            ], 422);
        }

        $auditLog->execute(
            action: 'seller.shop.banner_uploaded',
            actor: $user,
            target: $shop,
            after: ['banner_url' => $result['url']],
            request: $request
        );

        Log::info('Shop banner uploaded', [
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop banner uploaded successfully.',
            'data' => [
                'branding' => ShopBrandingResource::make($shop),
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        return response()->json([
            'success' => true,
            'message' => 'Shop branding retrieved successfully.',
            'data' => [
                'branding' => ShopBrandingResource::make($shop),
            ],
        ]);
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}

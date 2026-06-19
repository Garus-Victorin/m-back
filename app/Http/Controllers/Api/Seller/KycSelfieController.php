<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Actions\Seller\StoreSellerKycSelfieAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreKycSelfieRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KycSelfieController extends Controller
{
    public function store(
        StoreKycSelfieRequest $request,
        StoreSellerKycSelfieAction $action,
        RecordAuditLogAction $auditLog
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $file = $request->file('selfie');
        $result = $action->execute($shop, $user, $file);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to upload selfie.',
            ], 422);
        }

        $auditLog->execute(
            action: 'seller.kyc.selfie_uploaded',
            actor: $user,
            target: $shop,
            after: ['selfie_path' => $result['path']],
            request: $request
        );

        Log::info('KYC selfie uploaded', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'KYC selfie uploaded successfully.',
            'data' => [
                'selfie_url' => $result['url'],
                'status' => 'Selfie uploaded. Your KYC is under review.',
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $kycSubmission = $shop->kycSubmission;

        if (!$kycSubmission || !$kycSubmission->selfie_path) {
            return response()->json([
                'success' => false,
                'message' => 'No KYC selfie found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'KYC selfie information retrieved successfully.',
            'data' => [
                'has_selfie' => true,
                'uploaded_at' => $kycSubmission->updated_at->toDateTimeString(),
            ],
        ]);
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}

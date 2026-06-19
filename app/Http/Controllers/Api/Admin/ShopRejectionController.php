<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Audit\RecordAuditLogAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectShopRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopRejectionController extends Controller
{
    public function reject(RejectShopRequest $request, Shop $shop, RecordAuditLogAction $auditLog): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        // Vérifier que le shop peut être rejeté
        if (!in_array($shop->status, ['pending', 'draft'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or draft shops can be rejected.',
                'code' => 'SHOP_REJECTION_NOT_ALLOWED',
            ], 409);
        }

        // Mettre à jour le shop
        $shop->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('reason'),
            'rejected_at' => now(),
            'submitted_for_review_at' => null,
        ]);

        // Mettre à jour le user si nécessaire
        $shop->user->update(['kyc_status' => 'rejected']);

        $auditLog->execute(
            action: 'admin.shop.rejected',
            actor: $admin,
            target: $shop,
            after: [
                'status' => $shop->status,
                'rejection_reason' => $shop->rejection_reason,
                'rejected_at' => $shop->rejected_at?->toDateTimeString(),
            ],
            request: $request
        );

        Log::info('Shop rejected by admin', [
            'shop_id' => $shop->id,
            'admin_id' => $admin->id,
            'reason' => $shop->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop rejected successfully.',
            'data' => [
                'shop' => [
                    'id' => $shop->id,
                    'status' => $shop->status,
                    'rejection_reason' => $shop->rejection_reason,
                    'rejected_at' => $shop->rejected_at?->toDateTimeString(),
                ],
            ],
        ]);
    }

    public function showRejection(Request $request, Shop $shop): JsonResponse
    {
        $this->authorize('view', $shop);

        if ($shop->status !== 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'This shop has not been rejected.',
                'code' => 'SHOP_NOT_REJECTED',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shop rejection details retrieved successfully.',
            'data' => [
                'shop_id' => $shop->id,
                'status' => $shop->status,
                'rejection_reason' => $shop->rejection_reason,
                'rejected_at' => $shop->rejected_at?->toDateTimeString(),
                'can_resubmit' => true,
                'resubmit_requirements' => [
                    'Fix the issues mentioned in rejection reason',
                    'Update shop information if needed',
                    'Resubmit for review',
                ],
            ],
        ]);
    }
}

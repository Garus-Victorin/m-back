<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Seller\GetSellerDashboardAction;
use App\Actions\Seller\GetSellerFinanceSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function bootstrap(Request $request, GetSellerDashboardAction $dashboard, GetSellerFinanceSummaryAction $financeSummary): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $user->shop()->first();

        return response()->json([
            'success' => true,
            'message' => 'Seller bootstrap retrieved successfully.',
            'data' => [
                'user' => UserResource::make($user),
                'shop' => $shop ? ShopResource::make($shop) : null,
                'kyc_status' => $user->kyc_status,
                'capabilities' => [
                    'can_manage_products' => (bool) $shop,
                    'can_publish_products' => $shop?->status === 'active',
                    'can_manage_orders' => $shop?->status === 'active',
                    'can_request_withdrawals' => $shop?->status === 'active' && $user->kyc_status === 'verified',
                    'can_submit_shop_for_review' => (bool) $shop && in_array($shop->status, ['draft'], true),
                    'has_kyc_submission' => (bool) $shop?->kycSubmission,
                ],
                'finance_summary' => $shop ? $financeSummary->execute($shop) : [
                    'currency' => 'XOF',
                    'available_balance_cents' => 0,
                    'pending_balance_cents' => 0,
                    'total_earned_cents' => 0,
                    'total_withdrawn_cents' => 0,
                    'total_commissions_cents' => 0,
                    'pending_withdrawals_cents' => 0,
                    'min_withdrawal_cents' => GetSellerFinanceSummaryAction::MIN_WITHDRAWAL_CENTS,
                ],
                'dashboard' => $shop ? $dashboard->execute($shop) : [
                    'summary' => [
                        'today_revenue' => '0',
                        'pending_orders_count' => 0,
                        'ready_orders_count' => 0,
                        'out_of_stock_products_count' => 0,
                        'published_products_count' => 0,
                        'total_products_count' => 0,
                    ],
                    'recent_orders' => [],
                    'revenue_trend' => [],
                ],
            ],
        ]);
    }

    public function show(Request $request, GetSellerDashboardAction $dashboard): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $user->shop()->first();

        abort_unless($shop, 404, 'Shop not found.');

        return response()->json([
            'success' => true,
            'message' => 'Seller dashboard retrieved successfully.',
            'data' => $dashboard->execute($shop),
        ]);
    }
}

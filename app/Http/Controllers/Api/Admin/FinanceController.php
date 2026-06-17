<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Admin\ProcessSellerWithdrawalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSellerWithdrawalRequest;
use App\Http\Resources\Seller\SellerWithdrawalRequestResource;
use App\Models\SellerWithdrawalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function withdrawals(Request $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $query = SellerWithdrawalRequest::query()
            ->with(['shop', 'user', 'processor'])
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($shopId = $request->integer('shop_id')) {
            $query->where('shop_id', $shopId);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('idempotency_key', 'like', "%{$search}%")
                    ->orWhere('provider_reference', 'like', "%{$search}%")
                    ->orWhere('mobile_money_number', 'like', "%{$search}%")
                    ->orWhereHas('shop', fn (Builder $shopQuery) => $shopQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $withdrawals = $query
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Admin seller withdrawals retrieved successfully.',
            'data' => [
                'withdrawals' => SellerWithdrawalRequestResource::collection($withdrawals->getCollection()),
                'pagination' => [
                    'current_page' => $withdrawals->currentPage(),
                    'last_page' => $withdrawals->lastPage(),
                    'per_page' => $withdrawals->perPage(),
                    'total' => $withdrawals->total(),
                ],
            ],
        ]);
    }

    public function showWithdrawal(Request $request, SellerWithdrawalRequest $withdrawal): JsonResponse
    {
        $this->ensureAdmin($request->user());
        $withdrawal->load(['shop', 'user', 'processor']);

        return response()->json([
            'success' => true,
            'message' => 'Admin seller withdrawal retrieved successfully.',
            'data' => [
                'withdrawal' => SellerWithdrawalRequestResource::make($withdrawal),
            ],
        ]);
    }

    public function updateWithdrawal(
        UpdateSellerWithdrawalRequest $request,
        SellerWithdrawalRequest $withdrawal,
        ProcessSellerWithdrawalAction $action,
    ): JsonResponse {
        /** @var User $admin */
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $withdrawal = $action->execute($admin, $withdrawal, $request->validated(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Admin seller withdrawal updated successfully.',
            'data' => [
                'withdrawal' => SellerWithdrawalRequestResource::make($withdrawal),
            ],
        ]);
    }

    protected function ensureAdmin(?User $user): void
    {
        abort_unless($user && $user->role === 'admin', 403, 'Only admins can perform this action.');
        abort_unless($user->is_active, 403, 'Admin account is inactive.');
    }
}

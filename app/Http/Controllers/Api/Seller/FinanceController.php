<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Seller\CreateSellerWithdrawalRequestAction;
use App\Actions\Seller\GetSellerFinanceSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreSellerWithdrawalRequest;
use App\Http\Resources\Seller\SellerWithdrawalRequestResource;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    public function summary(Request $request, GetSellerFinanceSummaryAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        return response()->json([
            'success' => true,
            'message' => 'Seller finance summary retrieved successfully.',
            'data' => [
                'summary' => $action->execute($shop),
            ],
        ]);
    }

    public function withdrawals(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $withdrawals = $shop->withdrawalRequests()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Seller withdrawals retrieved successfully.',
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

    public function storeWithdrawal(StoreSellerWithdrawalRequest $request, CreateSellerWithdrawalRequestAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));

        if ($idempotencyKey === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => ['The Idempotency-Key header is required.'],
            ]);
        }

        if (! Str::isAscii($idempotencyKey) || strlen($idempotencyKey) > 100) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['The Idempotency-Key header must be a valid ASCII string up to 100 characters.'],
            ]);
        }

        $withdrawal = $action->execute(
            $user,
            $shop,
            $request->integer('amount_cents'),
            $idempotencyKey,
            $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Seller withdrawal request created successfully.',
            'data' => [
                'withdrawal' => SellerWithdrawalRequestResource::make($withdrawal),
            ],
        ], 201);
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}

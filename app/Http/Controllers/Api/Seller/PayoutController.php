<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Seller\ProcessSellerWithdrawalAction;
use App\Http\Controllers\Controller;
use App\Models\SellerWithdrawalRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayoutController extends Controller
{
    public function callback(
        Request $request,
        SellerWithdrawalRequest $withdrawal,
        ProcessSellerWithdrawalAction $action,
    ): JsonResponse {
        Log::info("Payout callback received", [
            "withdrawal_id" => $withdrawal->id,
            "payload" => $request->all(),
        ]);

        try {
            $result = $action->checkStatus($withdrawal);

            if ($result["success"]) {
                return response()->json([
                    "success" => true,
                    "message" => "Callback processed successfully",
                ]);
            }

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        $result["error"] ?? "Callback processing failed",
                ],
                400,
            );
        } catch (\Exception $e) {
            Log::error("Payout callback error", [
                "withdrawal_id" => $withdrawal->id,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Internal server error",
                ],
                500,
            );
        }
    }

    public function processWithdrawal(
        Request $request,
        SellerWithdrawalRequest $withdrawal,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        if ($withdrawal->user_id !== $user->id) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized",
                ],
                403,
            );
        }

        try {
            ProcessSellerWithdrawalJob::dispatch($withdrawal);

            return response()->json([
                "success" => true,
                "message" => "Withdrawal processing job dispatched",
                "data" => [
                    "withdrawal_id" => $withdrawal->id,
                    "status" => "queued",
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Withdrawal processing error", [
                "withdrawal_id" => $withdrawal->id,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => $e->getMessage(),
                ],
                400,
            );
        }
    }
}

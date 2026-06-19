<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Actions\Seller\SubmitSellerKycAction;
use App\Actions\Seller\SubmitSellerShopForReviewAction;
use App\Actions\Seller\UpdateSellerPayoutSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\CompleteSellerOnboardingRequest;
use App\Http\Resources\Seller\SellerOnboardingResource;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        return response()->json([
            'success' => true,
            'message' => 'Seller onboarding status retrieved successfully.',
            'data' => [
                'onboarding' => SellerOnboardingResource::make($user)->additional([
                    'shop' => $shop,
                ]),
            ],
        ]);
    }

    public function complete(
        CompleteSellerOnboardingRequest $request,
        SubmitSellerShopForReviewAction $submitShopAction,
        SubmitSellerKycAction $submitKycAction,
        UpdateSellerPayoutSettingsAction $updatePayoutAction,
        RecordAuditLogAction $auditLog
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        try {
            DB::beginTransaction();

            // Mettre à jour les informations du shop
            $shop->update([
                'name' => $request->input('shop.name'),
                'description' => $request->input('shop.description'),
                'phone' => $request->input('shop.phone'),
                'address' => $request->input('shop.address'),
                'city' => $request->input('shop.city'),
                'country' => $request->input('shop.country'),
            ]);

            // Mettre à jour les paramètres de payout
            $updatePayoutAction->execute($shop, [
                'payouts_enabled' => true,
                'payout_mobile_money_provider' => $request->input('payout.provider'),
                'payout_mobile_money_number' => $request->input('payout.number'),
            ]);

            // Soumettre le shop pour revue
            $submitShopAction->execute($shop, $user);

            // Soumettre le KYC si des documents sont fournis
            if ($request->hasFile('kyc.document_front') || $request->hasFile('kyc.document_back')) {
                $kycData = [
                    'document_type' => $request->input('kyc.document_type', 'id_card'),
                ];

                if ($request->hasFile('kyc.document_front')) {
                    $kycData['document_front_path'] = $request->file('kyc.document_front')->store(
                        "kyc/{$shop->id}",
                        'private'
                    );
                }

                if ($request->hasFile('kyc.document_back')) {
                    $kycData['document_back_path'] = $request->file('kyc.document_back')->store(
                        "kyc/{$shop->id}",
                        'private'
                    );
                }

                $submitKycAction->execute($shop, $user, $kycData);
            }

            $auditLog->execute(
                action: 'seller.onboarding.completed',
                actor: $user,
                target: $shop,
                after: [
                    'shop_status' => $shop->refresh()->status,
                    'kyc_status' => $user->refresh()->kyc_status,
                ],
                request: $request
            );

            DB::commit();

            Log::info('Seller onboarding completed', [
                'user_id' => $user->id,
                'shop_id' => $shop->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Seller onboarding completed successfully. Your shop and KYC are under review.',
                'data' => [
                    'shop_status' => $shop->status,
                    'kyc_status' => $user->kyc_status,
                    'next_steps' => [
                        'Wait for admin review (usually 24-48 hours)',
                        'You will receive a notification once your shop is approved',
                        'Check your email for updates',
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Seller onboarding failed', [
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete onboarding: ' . $e->getMessage(),
            ], 422);
        }
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}

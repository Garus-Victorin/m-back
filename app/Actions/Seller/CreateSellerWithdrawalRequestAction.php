<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\SellerKycSubmission;
use App\Models\SellerWithdrawalRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CreateSellerWithdrawalRequestAction
{
    public function __construct(
        protected GetSellerFinanceSummaryAction $financeSummary,
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function execute(User $user, Shop $shop, int $amountCents, string $idempotencyKey, ?Request $request = null): SellerWithdrawalRequest
    {
        if ($shop->status !== 'active') {
            throw new ConflictHttpException('Only active shops can request withdrawals.');
        }

        if ($user->kyc_status !== 'verified') {
            throw new ConflictHttpException('KYC verification is required before requesting a withdrawal.');
        }

        if ($amountCents < GetSellerFinanceSummaryAction::MIN_WITHDRAWAL_CENTS) {
            throw new UnprocessableEntityHttpException(sprintf(
                'The minimum withdrawal amount is %d cents.',
                GetSellerFinanceSummaryAction::MIN_WITHDRAWAL_CENTS,
            ));
        }

        $existingRequest = SellerWithdrawalRequest::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingRequest) {
            if ($existingRequest->user_id !== $user->id || $existingRequest->shop_id !== $shop->id || $existingRequest->amount_cents !== $amountCents) {
                throw new ConflictHttpException('This idempotency key has already been used for another withdrawal request.');
            }

            return $existingRequest;
        }

        $summary = $this->financeSummary->execute($shop);

        if ($amountCents > $summary['available_balance_cents']) {
            throw new ConflictHttpException('Insufficient available balance for this withdrawal request.');
        }

        [$provider, $number] = $this->resolvePayoutDestination($shop);

        $withdrawal = SellerWithdrawalRequest::create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'amount_cents' => $amountCents,
            'currency' => 'XOF',
            'mobile_money_provider' => $provider,
            'mobile_money_number' => $number,
            'status' => 'pending',
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->auditLog->execute(
            action: 'seller.withdrawal.requested',
            actor: $user,
            target: $withdrawal,
            after: [
                'status' => $withdrawal->status,
                'amount_cents' => $withdrawal->amount_cents,
                'mobile_money_provider' => $withdrawal->mobile_money_provider,
                'mobile_money_number' => $withdrawal->mobile_money_number,
            ],
            request: $request,
        );

        return $withdrawal;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolvePayoutDestination(Shop $shop): array
    {
        if ($shop->payouts_enabled === false) {
            throw new UnprocessableEntityHttpException('Payouts are disabled for this shop.');
        }

        if (filled($shop->payout_mobile_money_provider) && filled($shop->payout_mobile_money_number)) {
            return [$shop->payout_mobile_money_provider, $shop->payout_mobile_money_number];
        }

        /** @var SellerKycSubmission|null $kycSubmission */
        $kycSubmission = $shop->kycSubmission;

        if (! $kycSubmission || blank($kycSubmission->mobile_money_provider) || blank($kycSubmission->mobile_money_number)) {
            throw new UnprocessableEntityHttpException('A verified payout destination is required before requesting a withdrawal.');
        }

        return [$kycSubmission->mobile_money_provider, $kycSubmission->mobile_money_number];
    }
}

<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\SellerWithdrawalRequest;
use App\Services\Payout\PayoutServiceInterface;
use Illuminate\Support\Facades\Log;

class ProcessSellerWithdrawalAction
{
    public function __construct(
        protected PayoutServiceInterface $payoutService,
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function execute(SellerWithdrawalRequest $withdrawal): SellerWithdrawalRequest
    {
        if ($withdrawal->status !== 'pending') {
            throw new \RuntimeException('Only pending withdrawals can be processed.');
        }

        $result = $this->payoutService->processWithdrawal($withdrawal);

        if ($result['success']) {
            $withdrawal->update([
                'status' => 'processing',
                'provider_transaction_id' => $result['transaction_id'],
                'provider_status' => $result['status'],
                'provider_response' => json_encode($result),
                'provider_processed_at' => now(),
                'callback_url' => $this->payoutService->generateCallbackUrl($withdrawal),
            ]);

            $this->auditLog->execute(
                action: 'seller.withdrawal.processing',
                actor: $withdrawal->user,
                target: $withdrawal,
                after: [
                    'status' => $withdrawal->status,
                    'provider_transaction_id' => $withdrawal->provider_transaction_id,
                ],
            );

            return $withdrawal;
        }

        $withdrawal->update([
            'status' => 'failed',
            'failure_reason' => $result['error'],
            'provider_response' => json_encode($result),
        ]);

        $this->auditLog->execute(
            action: 'seller.withdrawal.failed',
            actor: $withdrawal->user,
            target: $withdrawal,
            after: [
                'status' => $withdrawal->status,
                'failure_reason' => $withdrawal->failure_reason,
            ],
        );

        Log::error('Withdrawal processing failed', [
            'withdrawal_id' => $withdrawal->id,
            'error' => $result['error'],
        ]);

        return $withdrawal;
    }

    public function checkStatus(SellerWithdrawalRequest $withdrawal): array
    {
        if (blank($withdrawal->provider_transaction_id)) {
            return [
                'success' => false,
                'error' => 'No provider transaction ID available',
            ];
        }

        $result = $this->payoutService->checkStatus($withdrawal->provider_transaction_id);

        if ($result['success']) {
            $status = $result['status'];

            if (in_array($status, ['completed', 'paid', 'success'])) {
                $withdrawal->update([
                    'status' => 'paid',
                    'provider_status' => $status,
                    'provider_response' => json_encode($result),
                ]);

                $this->auditLog->execute(
                    action: 'seller.withdrawal.paid',
                    actor: $withdrawal->user,
                    target: $withdrawal,
                    after: [
                        'status' => $withdrawal->status,
                    ],
                );
            } elseif (in_array($status, ['failed', 'rejected', 'cancelled'])) {
                $withdrawal->update([
                    'status' => 'failed',
                    'failure_reason' => 'Provider transaction failed',
                    'provider_status' => $status,
                    'provider_response' => json_encode($result),
                ]);

                $this->auditLog->execute(
                    action: 'seller.withdrawal.failed',
                    actor: $withdrawal->user,
                    target: $withdrawal,
                    after: [
                        'status' => $withdrawal->status,
                        'failure_reason' => $withdrawal->failure_reason,
                    ],
                );
            }

            return [
                'success' => true,
                'status' => $status,
                'data' => $result['data'],
            ];
        }

        return [
            'success' => false,
            'error' => $result['error'],
        ];
    }
}

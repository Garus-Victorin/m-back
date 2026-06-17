<?php

namespace App\Actions\Admin;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\SellerWithdrawalRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProcessSellerWithdrawalAction
{
    /**
     * @var array<string, array<int, string>>
     */
    protected const ALLOWED_TRANSITIONS = [
        'pending' => ['processing', 'rejected'],
        'processing' => ['paid', 'failed'],
    ];

    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(User $admin, SellerWithdrawalRequest $withdrawal, array $attributes, ?Request $request = null): SellerWithdrawalRequest
    {
        $targetStatus = (string) $attributes['status'];
        $currentStatus = $withdrawal->status;

        if (! in_array($targetStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
            throw new ConflictHttpException('Invalid seller withdrawal status transition.');
        }

        $providerReference = array_key_exists('provider_reference', $attributes)
            ? $attributes['provider_reference']
            : $withdrawal->provider_reference;

        if ($targetStatus === 'paid' && blank($providerReference)) {
            throw ValidationException::withMessages([
                'provider_reference' => ['The provider reference is required when a withdrawal is marked as paid.'],
            ]);
        }

        if (in_array($targetStatus, ['failed', 'rejected'], true) && blank($attributes['failure_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'failure_reason' => ['A failure reason is required when a withdrawal is failed or rejected.'],
            ]);
        }

        $before = $this->auditSnapshot($withdrawal);

        $withdrawal->update([
            'status' => $targetStatus,
            'provider_reference' => $providerReference,
            'failure_reason' => in_array($targetStatus, ['failed', 'rejected'], true)
                ? trim((string) $attributes['failure_reason'])
                : null,
            'processed_by' => $admin->id,
            'processed_at' => in_array($targetStatus, ['paid', 'failed', 'rejected'], true) ? now() : null,
        ]);

        $withdrawal = $withdrawal->fresh(['shop', 'user', 'processor']);

        $this->auditLog->execute(
            action: 'admin.seller_withdrawal.updated',
            actor: $admin,
            target: $withdrawal,
            before: $before,
            after: $this->auditSnapshot($withdrawal),
            request: $request,
        );

        return $withdrawal;
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditSnapshot(SellerWithdrawalRequest $withdrawal): array
    {
        return [
            'status' => $withdrawal->status,
            'amount_cents' => $withdrawal->amount_cents,
            'provider_reference' => $withdrawal->provider_reference,
            'failure_reason' => $withdrawal->failure_reason,
            'processed_by' => $withdrawal->processed_by,
            'processed_at' => $withdrawal->processed_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Actions\Admin;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\SellerKycSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReviewSellerKycAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $admin, SellerKycSubmission $submission, array $attributes, ?Request $request = null): SellerKycSubmission
    {
        if ($submission->status !== 'pending') {
            throw new ConflictHttpException('Only pending KYC submissions can be reviewed.');
        }

        $targetStatus = (string) $attributes['status'];

        if ($targetStatus === 'rejected' && blank($attributes['rejection_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'rejection_reason' => ['A rejection reason is required when rejecting a KYC submission.'],
            ]);
        }

        $before = $this->auditSnapshot($submission);

        $submission->update([
            'status' => $targetStatus,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $targetStatus === 'rejected'
                ? trim((string) $attributes['rejection_reason'])
                : null,
        ]);

        $submission->user->forceFill([
            'kyc_status' => $targetStatus,
            'kyc_document_url' => $submission->document_front_path,
        ])->save();

        $submission = $submission->fresh(['user', 'shop', 'reviewer']);

        $this->auditLog->execute(
            action: 'admin.seller_kyc.reviewed',
            actor: $admin,
            target: $submission,
            before: $before,
            after: $this->auditSnapshot($submission),
            request: $request,
        );

        return $submission;
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditSnapshot(SellerKycSubmission $submission): array
    {
        return [
            'status' => $submission->status,
            'reviewed_by' => $submission->reviewed_by,
            'reviewed_at' => $submission->reviewed_at?->toISOString(),
            'rejection_reason' => $submission->rejection_reason,
            'document_front_path' => $submission->document_front_path,
            'document_back_path' => $submission->document_back_path,
        ];
    }
}

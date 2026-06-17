<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\SellerKycSubmission;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;

class SubmitSellerKycAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, Shop $shop, array $attributes, ?Request $request = null): SellerKycSubmission
    {
        $existingSubmission = SellerKycSubmission::query()
            ->where('shop_id', $shop->id)
            ->where('user_id', $user->id)
            ->first();

        $before = $existingSubmission ? [
            'status' => $existingSubmission->status,
            'document_front_path' => $existingSubmission->document_front_path,
            'document_back_path' => $existingSubmission->document_back_path,
            'reviewed_by' => $existingSubmission->reviewed_by,
            'reviewed_at' => $existingSubmission->reviewed_at?->toISOString(),
            'rejection_reason' => $existingSubmission->rejection_reason,
        ] : null;

        $submission = SellerKycSubmission::query()->updateOrCreate(
            ['shop_id' => $shop->id, 'user_id' => $user->id],
            [
                'user_id' => $user->id,
                'document_type' => $attributes['document_type'],
                'document_number' => $attributes['document_number'] ?? null,
                'document_front_path' => $attributes['document_front_path'],
                'document_back_path' => $attributes['document_back_path'] ?? null,
                'mobile_money_provider' => $attributes['mobile_money_provider'],
                'mobile_money_number' => $attributes['mobile_money_number'],
                'notes' => $attributes['notes'] ?? null,
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ],
        );

        $user->forceFill([
            'kyc_status' => 'pending',
            'kyc_document_url' => $submission->document_front_path,
        ])->save();

        $submission = $submission->fresh(['user', 'shop', 'reviewer']);

        $this->auditLog->execute(
            action: 'seller.kyc.submitted',
            actor: $user,
            target: $submission,
            before: $before,
            after: [
                'status' => $submission->status,
                'document_front_path' => $submission->document_front_path,
                'document_back_path' => $submission->document_back_path,
            ],
            request: $request,
        );

        return $submission;
    }
}

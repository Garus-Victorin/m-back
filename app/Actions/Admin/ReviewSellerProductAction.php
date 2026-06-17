<?php

namespace App\Actions\Admin;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReviewSellerProductAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(User $admin, Product $product, array $attributes, ?Request $request = null): Product
    {
        $decision = (string) $attributes['decision'];

        if ($decision !== 'suspended' && $product->moderation_status !== 'pending_review') {
            throw new ConflictHttpException('Only pending review products can be approved or rejected.');
        }

        if ($decision === 'suspended' && $product->moderation_status !== 'approved') {
            throw new ConflictHttpException('Only approved products can be suspended.');
        }

        if (in_array($decision, ['rejected', 'suspended'], true) && blank($attributes['rejection_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'rejection_reason' => ['A reason is required for this review decision.'],
            ]);
        }

        $before = [
            'status' => $product->status,
            'moderation_status' => $product->moderation_status,
            'is_active' => $product->is_active,
            'rejection_reason' => $product->rejection_reason,
        ];

        $updates = match ($decision) {
            'approved' => [
                'status' => 'published',
                'moderation_status' => 'approved',
                'is_active' => true,
                'rejection_reason' => null,
                'archived_at' => null,
            ],
            'rejected' => [
                'status' => 'draft',
                'moderation_status' => 'rejected',
                'is_active' => true,
                'rejection_reason' => trim((string) $attributes['rejection_reason']),
            ],
            'suspended' => [
                'status' => 'published',
                'moderation_status' => 'suspended',
                'is_active' => false,
                'rejection_reason' => trim((string) $attributes['rejection_reason']),
            ],
        };

        $updates['reviewed_by'] = $admin->id;
        $updates['reviewed_at'] = now();

        $product->update($updates);
        $product = $product->fresh(['shop', 'category', 'images', 'variants']);

        $this->auditLog->execute(
            action: 'admin.product.reviewed',
            actor: $admin,
            target: $product,
            before: $before,
            after: [
                'status' => $product->status,
                'moderation_status' => $product->moderation_status,
                'is_active' => $product->is_active,
                'rejection_reason' => $product->rejection_reason,
            ],
            request: $request,
        );

        return $product;
    }
}

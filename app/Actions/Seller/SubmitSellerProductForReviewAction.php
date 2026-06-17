<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SubmitSellerProductForReviewAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function execute(User $user, Product $product, ?Request $request = null): Product
    {
        $shop = $product->shop;

        if ($shop->user_id !== $user->id) {
            throw new ConflictHttpException('This product does not belong to the current seller.');
        }

        if ($shop->status !== 'active') {
            throw ValidationException::withMessages([
                'shop' => ['Only active shops can submit products for review.'],
            ]);
        }

        if ($user->kyc_status !== 'verified') {
            throw ValidationException::withMessages([
                'kyc' => ['KYC must be verified before submitting a product for review.'],
            ]);
        }

        if ($product->status === 'archived') {
            throw new ConflictHttpException('Archived products must be restored before review submission.');
        }

        if ($product->moderation_status === 'pending_review') {
            throw new ConflictHttpException('This product is already pending review.');
        }

        $errors = [];

        if (! $product->category_id) {
            $errors['category_id'] = ['A category is required before submitting a product for review.'];
        }

        if ($product->price <= 0) {
            $errors['price'] = ['A valid positive price is required before submitting a product for review.'];
        }

        if ($product->images()->count() === 0) {
            $errors['images'] = ['At least one product image is required before submitting a product for review.'];
        }

        if ($product->variants()->exists() && $product->variants()->where('is_active', true)->count() === 0) {
            $errors['variants'] = ['At least one active variant is required before submitting a product with variants for review.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $before = [
            'status' => $product->status,
            'moderation_status' => $product->moderation_status,
            'submitted_for_review_at' => $product->submitted_for_review_at?->toISOString(),
        ];

        $product->update([
            'status' => 'draft',
            'moderation_status' => 'pending_review',
            'submitted_for_review_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'archived_at' => null,
            'is_active' => true,
        ]);

        $product = $product->fresh(['shop', 'category', 'images', 'variants']);

        $this->auditLog->execute(
            action: 'seller.product.submitted_for_review',
            actor: $user,
            target: $product,
            before: $before,
            after: [
                'status' => $product->status,
                'moderation_status' => $product->moderation_status,
                'submitted_for_review_at' => $product->submitted_for_review_at?->toISOString(),
            ],
            request: $request,
        );

        return $product;
    }
}

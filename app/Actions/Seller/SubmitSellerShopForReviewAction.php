<?php

namespace App\Actions\Seller;

use App\Models\Shop;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SubmitSellerShopForReviewAction
{
    public function execute(User $user, Shop $shop): Shop
    {
        if ($shop->status === 'active') {
            throw new ConflictHttpException('This shop is already active.');
        }

        if ($shop->status === 'suspended') {
            throw new ConflictHttpException('Suspended shops cannot be submitted for review.');
        }

        $missingFields = collect(['name', 'phone', 'email', 'address', 'city'])
            ->filter(fn (string $field) => blank($shop->{$field}))
            ->values()
            ->all();

        if ($missingFields !== []) {
            throw new UnprocessableEntityHttpException(
                'Shop profile is incomplete. Missing fields: '.implode(', ', $missingFields).'.',
            );
        }

        $kycSubmission = $shop->kycSubmission;

        if (! $kycSubmission) {
            throw new UnprocessableEntityHttpException('A KYC submission is required before requesting shop review.');
        }

        if ($kycSubmission->status === 'rejected') {
            throw new UnprocessableEntityHttpException('The current KYC submission was rejected. Please resubmit before requesting shop review.');
        }

        $shop->forceFill([
            'status' => 'pending',
            'submitted_at' => now(),
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        $user->forceFill([
            'kyc_status' => $kycSubmission->status,
        ])->save();

        return $shop->fresh();
    }
}

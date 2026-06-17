<?php

namespace App\Policies;

use App\Models\SellerWithdrawalRequest;
use App\Models\User;

class WithdrawalRequestPolicy
{
    public function view(User $user, SellerWithdrawalRequest $withdrawalRequest): bool
    {
        return $this->ownsRequest($user, $withdrawalRequest);
    }

    protected function ownsRequest(User $user, SellerWithdrawalRequest $withdrawalRequest): bool
    {
        return $user->role === 'seller'
            && $user->is_active
            && $withdrawalRequest->user_id === $user->id
            && $withdrawalRequest->shop()->where('user_id', $user->id)->exists();
    }
}

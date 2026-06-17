<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function view(User $user, Shop $shop): bool
    {
        return $this->ownsShop($user, $shop);
    }

    public function update(User $user, Shop $shop): bool
    {
        return $this->ownsShop($user, $shop);
    }

    protected function ownsShop(User $user, Shop $shop): bool
    {
        return $user->role === 'seller'
            && $user->is_active
            && $shop->user_id === $user->id;
    }
}

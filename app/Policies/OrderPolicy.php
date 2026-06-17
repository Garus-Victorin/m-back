<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order);
    }

    protected function ownsOrder(User $user, Order $order): bool
    {
        return $user->role === 'seller'
            && $user->is_active
            && $order->shop()->where('user_id', $user->id)->exists();
    }
}

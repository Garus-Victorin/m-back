<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function manageImages(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    protected function ownsProduct(User $user, Product $product): bool
    {
        return $user->role === 'seller'
            && $user->is_active
            && $product->shop()->where('user_id', $user->id)->exists();
    }
}

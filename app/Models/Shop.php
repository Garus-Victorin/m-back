<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'slug', 'description', 'phone', 'email', 'address', 'city', 'payout_beneficiary_name', 'payout_mobile_money_provider', 'payout_mobile_money_number', 'payouts_enabled', 'status', 'submitted_at', 'activated_at', 'suspended_at', 'suspension_reason'])]
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function kycSubmission(): HasOne
    {
        return $this->hasOne(SellerKycSubmission::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(SellerWithdrawalRequest::class);
    }
}

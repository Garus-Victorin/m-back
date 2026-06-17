<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id',
    'user_id',
    'amount_cents',
    'currency',
    'mobile_money_provider',
    'mobile_money_number',
    'status',
    'idempotency_key',
    'processed_by',
    'processed_at',
    'failure_reason',
    'provider_reference',
])]
class SellerWithdrawalRequest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

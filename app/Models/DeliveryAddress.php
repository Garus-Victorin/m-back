<?php

namespace App\Models;

use Database\Factories\DeliveryAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'phone',
    'address_line1',
    'address_line2',
    'city',
    'state',
    'postal_code',
    'country',
    'latitude',
    'longitude',
    'is_default',
])]
class DeliveryAddress extends Model
{
    /** @use HasFactory<DeliveryAddressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
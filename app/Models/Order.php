<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number',
    'customer_id',
    'shop_id',
    'delivery_address_id',
    'delivery_method',
    'otp_code',
    'status',
    'subtotal',
    'delivery_fee',
    'total',
    'platform_commission',
    'seller_amount',
    'delivery_amount',
    'payment_details',
    'paid_at',
    'inventory_reserved_at',
    'inventory_committed_at',
    'inventory_released_at',
    'prepared_at',
    'picked_up_at',
    'delivered_at',
    'cancelled_at',
    'cancel_reason',
    'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'seller_amount' => 'decimal:2',
            'delivery_amount' => 'decimal:2',
            'payment_details' => 'array',
            'variants' => 'array',
            'paid_at' => 'datetime',
            'inventory_reserved_at' => 'datetime',
            'inventory_committed_at' => 'datetime',
            'inventory_released_at' => 'datetime',
            'prepared_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered' && $this->delivered_at !== null;
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' && $this->cancelled_at !== null;
    }

    public function isReadyForPickup(): bool
    {
        return $this->status === 'ready_for_pickup' && $this->prepared_at !== null;
    }

    public function isInDelivery(): bool
    {
        return $this->status === 'in_delivery' && $this->picked_up_at !== null;
    }
}

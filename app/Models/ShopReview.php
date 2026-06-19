<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'order_id',
        'customer_id',
        'rating',
        'comment',
        'images',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'images' => 'array',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(User $approver): void
    {
        $this->update([
            'is_approved' => true,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public static function createForOrder(
        Order $order,
        int $rating,
        ?string $comment = null,
        ?array $images = null
    ): self {
        return self::create([
            'shop_id' => $order->shop_id,
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'rating' => $rating,
            'comment' => $comment,
            'images' => $images,
            'is_approved' => false,
        ]);
    }
}

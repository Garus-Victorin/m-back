<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'customer_id',
        'order_id',
        'subject',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SellerMessage::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(SellerMessage::class, 'last_message_id');
    }

    public function markAsReadForSeller(): void
    {
        $this->update(['seller_unread_count' => 0]);
    }

    public function markAsReadForCustomer(): void
    {
        $this->update(['customer_unread_count' => 0]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'closed_at' => null,
        ]);
    }
}

<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'type' => $this['type'],
            'type_label' => $this->getTypeLabel($this['type']),
            'reference' => $this['reference'],
            'amount_cents' => $this['amount_cents'],
            'amount' => $this['amount_cents'] / 100,
            'currency' => $this['currency'],
            'status' => $this['status'],
            'status_label' => $this->getStatusLabel($this['type'], $this['status']),
            'created_at' => $this['created_at'] instanceof \DateTimeInterface
                ? $this['created_at']->toDateTimeString()
                : $this['created_at'],
            'metadata' => $this['metadata'] ?? [],
            'is_credit' => $this['amount_cents'] > 0,
            'is_debit' => $this['amount_cents'] < 0,
        ];
    }

    protected function getTypeLabel(string $type): string
    {
        return match ($type) {
            'sale' => 'Sale',
            'commission' => 'Commission',
            'withdrawal' => 'Withdrawal',
            'adjustment' => 'Adjustment',
            default => ucfirst($type),
        };
    }

    protected function getStatusLabel(string $type, string $status): string
    {
        if ($type === 'withdrawal') {
            return match ($status) {
                'pending' => 'Pending',
                'processing' => 'Processing',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'rejected' => 'Rejected',
                default => ucfirst($status),
            };
        }

        return ucfirst($status);
    }
}

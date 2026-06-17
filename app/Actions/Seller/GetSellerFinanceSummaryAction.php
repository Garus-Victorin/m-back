<?php

namespace App\Actions\Seller;

use App\Models\Shop;

class GetSellerFinanceSummaryAction
{
    public const MIN_WITHDRAWAL_CENTS = 500;

    /**
     * @return array<string, int|string>
     */
    public function execute(Shop $shop): array
    {
        $salesStatuses = ['paid', 'preparing', 'ready_for_pickup', 'picked_up', 'in_delivery', 'delivered'];
        $pendingOrderStatuses = ['paid', 'preparing', 'ready_for_pickup', 'picked_up', 'in_delivery'];
        $withdrawalReservedStatuses = ['pending', 'processing', 'paid'];
        $pendingWithdrawalStatuses = ['pending', 'processing'];

        $totalEarned = (int) round($shop->orders()->whereIn('status', $salesStatuses)->sum('seller_amount') * 100);
        $pendingBalance = (int) round($shop->orders()->whereIn('status', $pendingOrderStatuses)->sum('seller_amount') * 100);
        $deliverableBalance = (int) round($shop->orders()->where('status', 'delivered')->sum('seller_amount') * 100);
        $totalCommissions = (int) round($shop->orders()->whereIn('status', $salesStatuses)->sum('platform_commission') * 100);
        $totalWithdrawn = (int) $shop->withdrawalRequests()->where('status', 'paid')->sum('amount_cents');
        $pendingWithdrawals = (int) $shop->withdrawalRequests()->whereIn('status', $pendingWithdrawalStatuses)->sum('amount_cents');
        $reservedWithdrawals = (int) $shop->withdrawalRequests()->whereIn('status', $withdrawalReservedStatuses)->sum('amount_cents');

        return [
            'currency' => 'XOF',
            'available_balance_cents' => max(0, $deliverableBalance - $reservedWithdrawals),
            'pending_balance_cents' => $pendingBalance,
            'total_earned_cents' => $totalEarned,
            'total_withdrawn_cents' => $totalWithdrawn,
            'total_commissions_cents' => $totalCommissions,
            'pending_withdrawals_cents' => $pendingWithdrawals,
            'min_withdrawal_cents' => self::MIN_WITHDRAWAL_CENTS,
        ];
    }
}

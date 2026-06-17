<?php

namespace App\Actions\Seller;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Support\Carbon;

class GetSellerDashboardAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Shop $shop): array
    {
        $today = Carbon::today();
        $recentStatuses = ['paid', 'preparing', 'ready_for_pickup', 'picked_up', 'in_delivery'];
        $salesStatuses = ['paid', 'preparing', 'ready_for_pickup', 'picked_up', 'in_delivery', 'delivered'];

        $summary = [
            'today_revenue' => (string) $shop->orders()
                ->whereIn('status', $salesStatuses)
                ->whereDate('created_at', $today)
                ->sum('seller_amount'),
            'pending_orders_count' => $shop->orders()->where('status', 'paid')->count(),
            'ready_orders_count' => $shop->orders()->where('status', 'ready_for_pickup')->count(),
            'out_of_stock_products_count' => $shop->products()->where('stock', 0)->count(),
            'published_products_count' => $shop->products()->where('status', 'published')->where('moderation_status', 'approved')->count(),
            'total_products_count' => $shop->products()->count(),
        ];

        $recentOrders = $shop->orders()
            ->with(['customer', 'items.product', 'deliveryAddress'])
            ->whereIn('status', $recentStatuses)
            ->latest()
            ->limit(5)
            ->get();

        $revenueTrend = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($shop, $salesStatuses, $today): array {
                $date = $today->copy()->subDays($daysAgo);

                return [
                    'date' => $date->toDateString(),
                    'amount' => (string) $shop->orders()
                        ->whereIn('status', $salesStatuses)
                        ->whereDate('created_at', $date)
                        ->sum('seller_amount'),
                    'orders_count' => $shop->orders()
                        ->whereIn('status', $salesStatuses)
                        ->whereDate('created_at', $date)
                        ->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'summary' => $summary,
            'recent_orders' => OrderResource::collection($recentOrders),
            'revenue_trend' => $revenueTrend,
        ];
    }
}

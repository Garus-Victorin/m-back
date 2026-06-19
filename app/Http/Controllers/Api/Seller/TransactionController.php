<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Seller\SellerTransactionResource;
use App\Models\Order;
use App\Models\SellerWithdrawalRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        // Filtres
        $type = $request->query('type');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Récupérer les transactions
        $transactions = $this->getShopTransactions($shop, $type, $dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'message' => 'Seller transactions retrieved successfully.',
            'data' => [
                'transactions' => SellerTransactionResource::collection($transactions),
                'summary' => $this->getTransactionsSummary($transactions),
            ],
        ]);
    }

    protected function getShopTransactions(Shop $shop, ?string $type, ?string $dateFrom, ?string $dateTo): array
    {
        $transactions = [];

        // Filtres de date
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        // 1. Ventes (orders payées)
        if (!$type || $type === 'sale') {
            $query = Order::query()
                ->where('shop_id', $shop->id)
                ->where('status', 'paid');

            if ($from) {
                $query->where('paid_at', '>=', $from);
            }
            if ($to) {
                $query->where('paid_at', '<=', $to);
            }

            $sales = $query->orderBy('paid_at', 'desc')->get();

            foreach ($sales as $sale) {
                $transactions[] = [
                    'id' => 'sale-' . $sale->id,
                    'type' => 'sale',
                    'reference' => $sale->reference,
                    'amount_cents' => $sale->total_cents,
                    'currency' => $sale->currency,
                    'status' => 'completed',
                    'created_at' => $sale->paid_at,
                    'metadata' => [
                        'order_id' => $sale->id,
                        'customer_email' => $sale->customer_email,
                        'items_count' => $sale->items_count,
                    ],
                ];
            }
        }

        // 2. Commissions (à implémenter selon votre modèle de commission)
        if (!$type || $type === 'commission') {
            // Exemple: commission de 5% sur les ventes
            $query = Order::query()
                ->where('shop_id', $shop->id)
                ->where('status', 'paid');

            if ($from) {
                $query->where('paid_at', '>=', $from);
            }
            if ($to) {
                $query->where('paid_at', '<=', $to);
            }

            $commissionOrders = $query->get();

            foreach ($commissionOrders as $order) {
                $commissionAmount = (int) round($order->total_cents * 0.05); // 5% commission

                $transactions[] = [
                    'id' => 'commission-' . $order->id,
                    'type' => 'commission',
                    'reference' => 'COM-' . $order->reference,
                    'amount_cents' => -$commissionAmount, // Montant négatif car c'est une déduction
                    'currency' => $order->currency,
                    'status' => 'deducted',
                    'created_at' => $order->paid_at,
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_reference' => $order->reference,
                        'rate' => '5%',
                    ],
                ];
            }
        }

        // 3. Retraits
        if (!$type || $type === 'withdrawal') {
            $query = SellerWithdrawalRequest::query()
                ->where('shop_id', $shop->id);

            if ($from) {
                $query->where('created_at', '>=', $from);
            }
            if ($to) {
                $query->where('created_at', '<=', $to);
            }

            $withdrawals = $query->orderBy('created_at', 'desc')->get();

            foreach ($withdrawals as $withdrawal) {
                $transactions[] = [
                    'id' => 'withdrawal-' . $withdrawal->id,
                    'type' => 'withdrawal',
                    'reference' => $withdrawal->idempotency_key,
                    'amount_cents' => -$withdrawal->amount_cents, // Montant négatif car c'est un retrait
                    'currency' => $withdrawal->currency,
                    'status' => $withdrawal->status,
                    'created_at' => $withdrawal->created_at,
                    'metadata' => [
                        'withdrawal_id' => $withdrawal->id,
                        'provider' => $withdrawal->mobile_money_provider,
                        'number' => $withdrawal->mobile_money_number,
                    ],
                ];
            }
        }

        // Trier par date (plus récent en premier)
        usort($transactions, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return $transactions;
    }

    protected function getTransactionsSummary(array $transactions): array
    {
        $summary = [
            'total_transactions' => count($transactions),
            'total_sales' => 0,
            'total_commissions' => 0,
            'total_withdrawals' => 0,
            'net_balance' => 0,
        ];

        foreach ($transactions as $transaction) {
            switch ($transaction['type']) {
                case 'sale':
                    $summary['total_sales'] += $transaction['amount_cents'];
                    $summary['net_balance'] += $transaction['amount_cents'];
                    break;
                case 'commission':
                    $summary['total_commissions'] += abs($transaction['amount_cents']);
                    $summary['net_balance'] += $transaction['amount_cents'];
                    break;
                case 'withdrawal':
                    $summary['total_withdrawals'] += abs($transaction['amount_cents']);
                    $summary['net_balance'] += $transaction['amount_cents'];
                    break;
            }
        }

        return $summary;
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}

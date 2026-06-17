<?php

namespace App\Actions\Seller;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MarkSellerOrderReadyAction
{
    public function execute(Order $order): Order
    {
        if (! in_array($order->status, ['paid', 'preparing'], true)) {
            throw new ConflictHttpException(sprintf(
                'Order cannot be marked ready from status [%s].',
                $order->status,
            ));
        }

        $order->forceFill([
            'status' => 'ready_for_pickup',
            'prepared_at' => Carbon::now(),
        ])->save();

        return $order->fresh(['customer', 'items.product', 'items.productVariant', 'deliveryAddress', 'shop']);
    }
}

<?php

namespace App\Listeners;

use App\Actions\Inventory\ReleaseReservedStockAction;
use App\Events\OrderCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderCancelledListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReleaseReservedStockAction $releaseStockAction,
    ) {
    }

    public function handle(OrderCancelled $event): void
    {
        $this->releaseStockAction->execute(
            $event->order,
            'Order cancelled: ' . ($event->reason ?? 'No reason provided')
        );
    }

    public function failed(OrderCancelled $event, \Throwable $exception): void
    {
        // Log the failure but don't throw exception to allow queue to continue
        report($exception);
    }
}

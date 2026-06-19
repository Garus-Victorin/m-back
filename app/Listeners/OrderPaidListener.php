<?php

namespace App\Listeners;

use App\Actions\Inventory\CommitReservedStockAction;
use App\Events\OrderPaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderPaidListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected CommitReservedStockAction $commitStockAction,
    ) {
    }

    public function handle(OrderPaid $event): void
    {
        // Selon la règle métier, on commit le stock au paiement
        $this->commitStockAction->execute($event->order);
    }

    public function failed(OrderPaid $event, \Throwable $exception): void
    {
        // Log the failure but don't throw exception to allow queue to continue
        report($exception);
    }
}

<?php

namespace App\Console\Commands;

use App\Actions\Seller\ProcessSellerWithdrawalAction;
use App\Models\SellerWithdrawalRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingWithdrawals extends Command
{
    protected $signature = 'withdrawals:process-pending
                            {--limit=10 : The number of withdrawals to process}
                            {--force : Force processing even if already being processed}';

    protected $description = 'Process pending seller withdrawal requests';

    public function handle(ProcessSellerWithdrawalAction $action): int
    {
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $query = SellerWithdrawalRequest::query()
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc');

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', now());
            });
        }

        $withdrawals = $query->take($limit)->get();

        if ($withdrawals->isEmpty()) {
            $this->info('No pending withdrawals to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$withdrawals->count()} pending withdrawal(s)...");

        $processed = 0;
        $failed = 0;

        foreach ($withdrawals as $withdrawal) {
            try {
                $action->execute($withdrawal);
                $this->info("Processed withdrawal #{$withdrawal->id}");
                $processed++;
            } catch (\Exception $e) {
                Log::error("Failed to process withdrawal #{$withdrawal->id}", [
                    'error' => $e->getMessage(),
                ]);

                $withdrawal->increment('retry_count');
                $withdrawal->update([
                    'next_retry_at' => now()->addMinutes(5 * $withdrawal->retry_count),
                ]);

                $this->error("Failed to process withdrawal #{$withdrawal->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Processed {$processed} withdrawals, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

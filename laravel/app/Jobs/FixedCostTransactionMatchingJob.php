<?php

namespace App\Jobs;

use App\Services\TransactionFixedCostMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FixedCostTransactionMatchingJob implements ShouldQueue
{
    use Queueable;

    public function handle(TransactionFixedCostMatchingService $service): void
    {
        $results = $service->matchAllUnmatchedForAllUsers();

        Log::info('[FixedCostTransactionMatchingJob] Ergebnis', $results);
    }
}


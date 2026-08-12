<?php

namespace App\Jobs\Scheduled;

use App\Services\TransactionFixedCostMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FixedCostTransactionMatchingJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public static function description(): string
    {
        return 'Unzugeordnete Transaktionen automatisch mit Fixkosten abgleichen';
    }

    public function handle(TransactionFixedCostMatchingService $service): void
    {
        $results = $service->matchAllUnmatchedForAllUsers();

        Log::channel('database')->info('Fixed cost matching wurde ausgeführt.', [
            'event' => 'fixed_costs.matching.completed',
            'results' => $results,
        ]);
    }
}


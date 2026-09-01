<?php

namespace App\Jobs\Scheduled;

use App\Models\User;
use App\Services\TransactionCategorizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CategorizeTransactionsJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public static function description(): string
    {
        return 'Nicht kategorisierte Transaktionen anhand von Kategorieregeln automatisch kategorisieren';
    }

    public function handle(TransactionCategorizationService $service): void
    {
        $totalProcessed = 0;
        $totalCategorized = 0;
        $totalSkipped = 0;

        User::query()->each(function (User $user) use ($service, &$totalProcessed, &$totalCategorized, &$totalSkipped): void {
            $results = $service->categorizeUncategorized($user->id);

            $totalProcessed += $results['processed'];
            $totalCategorized += $results['categorized'];
            $totalSkipped += $results['skipped'];
        });

        Log::channel('database')->info('Transaktionskategorisierung wurde ausgeführt.', [
            'event' => 'transactions.categorization.completed',
            'results' => [
                'processed' => $totalProcessed,
                'categorized' => $totalCategorized,
                'skipped' => $totalSkipped,
            ],
        ]);
    }
}

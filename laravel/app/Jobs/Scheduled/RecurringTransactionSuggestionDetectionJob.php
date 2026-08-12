<?php

namespace App\Jobs\Scheduled;

use App\Services\RecurringTransactionSuggestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecurringTransactionSuggestionDetectionJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public static function description(): string
    {
        return 'Vorschläge für wiederkehrende Transaktionen erkennen';
    }

    public function handle(RecurringTransactionSuggestionService $service): void
    {
        $results = $service->detectForAllUsers();

        Log::channel('database')->info('Erkennung wiederkehrender Transaktionen wurde ausgeführt.', [
            'event' => 'transactions.recurring_suggestion_detection.completed',
            'results' => $results,
        ]);
    }
}


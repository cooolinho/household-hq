<?php

namespace App\Jobs;

use App\Services\RecurringTransactionSuggestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecurringTransactionSuggestionDetectionJob implements ShouldQueue
{
    use Queueable;

    public function handle(RecurringTransactionSuggestionService $service): void
    {
        $results = $service->detectForAllUsers();

        Log::info('[RecurringTransactionSuggestionDetectionJob] Ergebnis', $results);
    }
}


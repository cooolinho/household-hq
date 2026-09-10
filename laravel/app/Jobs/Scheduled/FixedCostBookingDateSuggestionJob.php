<?php

namespace App\Jobs\Scheduled;

use App\Services\FixedCost\FixedCostBookingDateSuggestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FixedCostBookingDateSuggestionJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public static function description(): string
    {
        return 'Buchungstermin-Abweichungen für Fixkosten erkennen';
    }

    public function handle(FixedCostBookingDateSuggestionService $service): void
    {
        $results = $service->detectForAllUsers();

        Log::channel('database')->info('Buchungstermin-Vorschläge wurden aktualisiert.', [
            'event' => 'fixed_costs.booking_date_suggestion_detection.completed',
            'results' => $results,
        ]);
    }
}

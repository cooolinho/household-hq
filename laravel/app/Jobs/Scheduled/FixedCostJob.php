<?php

namespace App\Jobs\Scheduled;

use App\Services\FixedCostNextBookingDateUpdater;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FixedCostJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public static function description(): string
    {
        return 'Nächste Buchungsdaten für alle Fixkosten aktualisieren';
    }

    public function handle(FixedCostNextBookingDateUpdater $updater): void
    {
        $updater->updateDueDates();

        Log::channel('database')->info('Fixed cost due dates wurden aktualisiert.', [
            'event' => 'fixed_costs.due_dates.updated',
        ]);
    }
}

<?php

namespace App\Jobs\Scheduled;

use App\Services\FixedCostNextBookingDateUpdater;
use App\Services\Reminder\ReminderDispatcher;
use App\Settings\FixedCostSettings;
use App\Settings\ReminderSettings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendRemindersJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public static function description(): string
    {
        return 'Fällige Erinnerungen prüfen und versenden';
    }

    public function handle(
        ReminderDispatcher              $dispatcher,
        ReminderSettings                $settings,
        FixedCostNextBookingDateUpdater $updater,
        FixedCostSettings               $fixedCostSettings,
    ): void
    {
        try {
            if (!$settings->enabled) {
                Log::info('Reminders are disabled globally, skipping reminder job.');
                Log::channel('database')->info('Reminder-Job wurde global übersprungen.', [
                    'event' => 'reminders.skipped',
                    'reason' => 'disabled_globally',
                ]);

                return;
            }

            $now = CarbonImmutable::now();

            // Erinnerungen auf FixedCost::next_booking_date brauchen ein aktuelles Datum;
            // das nächtliche FixedCostJob deckt das normalerweise ab, hier zusätzlich
            // als Sicherheitsnetz, damit stündliche Erinnerungen nicht auf veraltete Werte laufen.
            if ($fixedCostSettings->update_due_dates_enabled) {
                $updater->updateDueDates($now->startOfDay());
            }

            $sent = $dispatcher->dispatchDue($now);

            Log::channel('database')->info('Reminder-Job wurde abgeschlossen.', [
                'event' => 'reminders.completed',
                'sent' => $sent,
                'ran_at' => $now->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            $this->fail($e);
            Log::channel('database')->error('Fehler beim Ausführen des Reminder-Jobs.', [
                'event' => 'reminders.failed',
                'message' => $e->getMessage(),
            ]);
        }
    }
}

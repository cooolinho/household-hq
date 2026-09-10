<?php

use App\Jobs\Scheduled\CheckBudgetThresholdsJob;
use App\Jobs\Scheduled\FetchImapDocumentsJob;
use App\Jobs\Scheduled\FixedCostBookingDateSuggestionJob;
use App\Jobs\Scheduled\FixedCostJob;
use App\Jobs\Scheduled\FixedCostTransactionMatchingJob;
use App\Jobs\Scheduled\RecurringTransactionSuggestionDetectionJob;
use App\Jobs\Scheduled\RefreshTransactionStatisticsJob;
use App\Jobs\Scheduled\SendRemindersJob;
use App\Settings\FixedCostSettings;
use App\Settings\ImapImportSettings;
use App\Settings\ReminderSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (app()->runningUnitTests()) {
    return;
}

// Jobs ohne Settings-Abhaengigkeit zuerst registrieren, damit sie auch dann
// laufen, wenn die Settings unten nicht aufgeloest werden koennen.

// Refresh transaction statistics cache nightly
Schedule::job(new RefreshTransactionStatisticsJob())
    ->dailyAt('03:00');

// Check budget thresholds and send notifications
Schedule::job(new CheckBudgetThresholdsJob())
    ->dailyAt('07:00');

if (!Schema::hasTable('settings')) {
    return;
}

try {
    $fixedCostSettings = app(FixedCostSettings::class);
    $imapImportSettings = app(ImapImportSettings::class);
    $reminderSettings = app(ReminderSettings::class);

    // Spatie laedt Settings-Properties lazy beim ersten Zugriff (__get), nicht
    // beim app()-Aufruf selbst. toArray() erzwingt den Zugriff auf alle
    // Properties hier an EINER Stelle, damit ein fehlendes Setting sofort und
    // vorhersehbar auffliegt, statt erst weiter unten bei irgendeinem der
    // einzelnen if ($settings->...)-Zugriffe.
    $fixedCostSettings->toArray();
    $imapImportSettings->toArray();
    $reminderSettings->toArray();
} catch (MissingSettings $exception) {
    // Ausstehende Settings-Migration (z.B. waehrend `php artisan migrate:settings`).
    // Diese Datei wird bei JEDEM Artisan-Aufruf geladen - ohne diesen Guard wuerde
    // eine neu deklarierte Settings-Property jeden Befehl abbrechen, inklusive der
    // Migration, die die fehlenden Werte ueberhaupt erst anlegt.
    Log::warning('Scheduler uebersprungen: Settings-Migrationen stehen aus.', [
        'exception' => $exception->getMessage(),
    ]);

    return;
}

if ($fixedCostSettings->update_due_dates_enabled) {
    Schedule::job(new FixedCostJob())
        ->dailyAt($fixedCostSettings->update_schedule_time);
}

if ($reminderSettings->enabled) {
    Schedule::job(new SendRemindersJob())
        ->hourly()
        ->withoutOverlapping();
}

if ($fixedCostSettings->matching_enabled) {
    Schedule::job(new FixedCostTransactionMatchingJob())
        ->dailyAt($fixedCostSettings->matching_schedule_time);
}

if ($fixedCostSettings->recurring_enabled) {
    Schedule::job(new RecurringTransactionSuggestionDetectionJob())
        ->dailyAt($fixedCostSettings->recurring_schedule_time);
}

if ($fixedCostSettings->booking_date_suggestions_enabled) {
    Schedule::job(new FixedCostBookingDateSuggestionJob())
        ->dailyAt($fixedCostSettings->booking_date_schedule_time);
}

if ($imapImportSettings->enabled) {
    $minutes = max(1, min(59, $imapImportSettings->schedule_minutes));

    Schedule::job(new FetchImapDocumentsJob())
        ->cron(sprintf('*/%d * * * *', $minutes))
        ->withoutOverlapping();
}

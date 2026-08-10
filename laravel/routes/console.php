<?php

use App\Jobs\FetchImapDocumentsJob;
use App\Jobs\FixedCostJob;
use App\Jobs\FixedCostTransactionMatchingJob;
use App\Jobs\RecurringTransactionSuggestionDetectionJob;
use App\Jobs\SendUpcomingFixedCostsReminderJob;
use App\Settings\FixedCostSettings;
use App\Settings\ImapImportSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (app()->runningUnitTests()) {
    return;
}

if (!Schema::hasTable('settings')) {
    return;
}

$fixedCostSettings = app(FixedCostSettings::class);
$imapImportSettings = app(ImapImportSettings::class);

Schedule::job(new FixedCostJob())
    ->dailyAt($fixedCostSettings->update_schedule_time);

if ($fixedCostSettings->reminders_enabled) {
    Schedule::job(new SendUpcomingFixedCostsReminderJob())
        ->dailyAt($fixedCostSettings->reminders_schedule_time);
}

if ($fixedCostSettings->matching_enabled) {
    Schedule::job(new FixedCostTransactionMatchingJob())
        ->dailyAt($fixedCostSettings->matching_schedule_time);
}

if ($fixedCostSettings->recurring_enabled) {
    Schedule::job(new RecurringTransactionSuggestionDetectionJob())
        ->dailyAt($fixedCostSettings->recurring_schedule_time);
}

if ($imapImportSettings->enabled) {
    $minutes = max(1, min(59, $imapImportSettings->schedule_minutes));

    Schedule::job(new FetchImapDocumentsJob())
        ->cron(sprintf('*/%d * * * *', $minutes))
        ->withoutOverlapping();
}


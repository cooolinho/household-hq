<?php

use App\Jobs\FetchImapDocumentsJob;
use App\Jobs\FixedCostJob;
use App\Jobs\FixedCostTransactionMatchingJob;
use App\Jobs\RecurringTransactionSuggestionDetectionJob;
use App\Jobs\SendUpcomingFixedCostsReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new FixedCostJob())
    ->dailyAt((string) config('fixed_costs.update_schedule.time', '00:15'));

if (config('fixed_costs.reminders.enabled', true)) {
    Schedule::job(new SendUpcomingFixedCostsReminderJob())
        ->dailyAt((string)config('fixed_costs.reminders.schedule_time', '07:00'));
}

if (config('fixed_costs.matching.enabled', true)) {
    Schedule::job(new FixedCostTransactionMatchingJob())
        ->dailyAt((string)config('fixed_costs.matching.schedule_time', '02:00'));
}

if (config('fixed_costs.recurring.enabled', true)) {
    Schedule::job(new RecurringTransactionSuggestionDetectionJob())
        ->dailyAt((string)config('fixed_costs.recurring.schedule_time', '03:00'));
}

if (config('imap_import.enabled', true)) {
    $minutes = max(1, min(59, (int)config('imap_import.schedule_minutes', 15)));

    Schedule::job(new FetchImapDocumentsJob())
        ->cron(sprintf('*/%d * * * *', $minutes))
        ->withoutOverlapping();
}


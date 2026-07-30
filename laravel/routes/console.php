<?php

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

$reminderTime = (string) config('fixed_costs.reminder_time', '07:00');

match ((string) config('fixed_costs.reminder_schedule', 'weekly')) {
    'daily' => Schedule::job(new SendUpcomingFixedCostsReminderJob())->dailyAt($reminderTime),
    'monthly' => Schedule::job(new SendUpcomingFixedCostsReminderJob())->monthlyOn(1, $reminderTime),
    default => Schedule::job(new SendUpcomingFixedCostsReminderJob())->weeklyOn(1, $reminderTime),
};

if (config('fixed_costs.matching.enabled', true)) {
    Schedule::job(new FixedCostTransactionMatchingJob())
        ->dailyAt((string)config('fixed_costs.matching.schedule_time', '02:00'));
}

if (config('fixed_costs.recurring.enabled', true)) {
    Schedule::job(new RecurringTransactionSuggestionDetectionJob())
        ->dailyAt((string)config('fixed_costs.recurring.schedule_time', '03:00'));
}


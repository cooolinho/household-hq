<?php

namespace App\Console\Commands;

use App\Jobs\Scheduled\SendUpcomingFixedCostsReminderJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-upcoming-fixed-costs-reminder-command')]
#[Description('Send upcoming fixed costs reminder')]
class SendUpcomingFixedCostsReminderCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        SendUpcomingFixedCostsReminderJob::dispatch();
    }
}

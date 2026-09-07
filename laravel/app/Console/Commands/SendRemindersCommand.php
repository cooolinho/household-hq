<?php

namespace App\Console\Commands;

use App\Jobs\Scheduled\SendRemindersJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-reminders')]
#[Description('Send due reminders')]
class SendRemindersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        SendRemindersJob::dispatch();
    }
}

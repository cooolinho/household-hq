<?php

namespace App\Jobs;

use App\Services\FixedCostNextBookingDateUpdater;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FixedCostJob implements ShouldQueue
{
    use Queueable;

    public function handle(FixedCostNextBookingDateUpdater $updater): void
    {
        $updater->updateDueDates();
    }
}

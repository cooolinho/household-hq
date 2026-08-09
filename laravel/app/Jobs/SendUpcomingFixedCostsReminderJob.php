<?php

namespace App\Jobs;

use App\Mail\UpcomingFixedCostsReminderMail;
use App\Models\Financial\FixedCost;
use App\Services\FixedCostNextBookingDateUpdater;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendUpcomingFixedCostsReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(FixedCostNextBookingDateUpdater $updater): void
    {
        try {
            $today = CarbonImmutable::today();
            $updater->updateDueDates($today);

            $dayEnd = $today->addDay();
            $weekEnd = $today->addWeek();
            $monthEnd = $today->addMonth();

            FixedCost::query()
                ->with(FixedCost::belongs_to_user)
                ->where(FixedCost::amount, '<', 0)
                ->whereNotNull(FixedCost::next_booking_date)
                ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $monthEnd->toDateString()])
                ->orderBy(FixedCost::next_booking_date)
                ->get()
                ->groupBy(FixedCost::user_id)
                ->each(function ($fixedCosts) use ($today, $dayEnd, $weekEnd, $monthEnd) {
                    $user = $fixedCosts->first()?->user;
                    if ($user === null || empty($user->email)) {
                        return;
                    }

                    $windows = [
                        'day' => $fixedCosts->filter(fn(FixedCost $fixedCost) => $this->isWithinRange($fixedCost, $today, $dayEnd))->values(),
                        'week' => $fixedCosts->filter(fn(FixedCost $fixedCost) => $this->isWithinRange($fixedCost, $dayEnd->addDay(), $weekEnd))->values(),
                        'month' => $fixedCosts->filter(fn(FixedCost $fixedCost) => $this->isWithinRange($fixedCost, $weekEnd->addDay(), $monthEnd))->values(),
                    ];

                    if ($windows['day']->isEmpty() && $windows['week']->isEmpty() && $windows['month']->isEmpty()) {
                        return;
                    }

                    Mail::to($user->email)->send(new UpcomingFixedCostsReminderMail($user->name, $windows, [
                        'day' => [$today, $dayEnd],
                        'week' => [$dayEnd->addDay(), $weekEnd],
                        'month' => [$weekEnd->addDay(), $monthEnd],
                    ]));
                });
        } catch (\Exception $e) {
            $this->fail($e);
            Log::error('Error sending upcoming fixed costs reminder: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function isWithinRange(FixedCost $fixedCost, CarbonImmutable $from, CarbonImmutable $until): bool
    {
        $date = $fixedCost->next_booking_date?->toImmutable()->startOfDay();

        return $date !== null && $date->betweenIncluded($from, $until);
    }
}


<?php

namespace App\Jobs;

use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostReminder;
use App\Models\User;
use App\Notifications\Financial\FixedCostReminderNotification;
use App\Services\FixedCostNextBookingDateUpdater;
use App\Settings\FixedCostSettings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendUpcomingFixedCostsReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(FixedCostNextBookingDateUpdater $updater, FixedCostSettings $settings): void
    {
        try {
            if (!$settings->reminders_enabled) {
                Log::info('Fixed cost reminders are disabled globally, skipping reminder job.');

                return;
            }

            $today = CarbonImmutable::today();
            $updater->updateDueDates($today);
            $maxLeadTimeDays = FixedCostReminder::leadTimeOptions()
                    |> array_keys(...)
                    |> max(...);
            $windowEnd = $today->addDays($maxLeadTimeDays);

            FixedCost::query()
                ->with(FixedCost::belongs_to_user)
                ->with(FixedCost::has_many_reminders)
                ->whereHas(FixedCost::has_many_reminders, function ($query): void {
                    $query->where(FixedCostReminder::enabled, true);
                })
                ->whereNotNull(FixedCost::next_booking_date)
                ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $windowEnd->toDateString()])
                ->get()
                ->each(function (FixedCost $fixedCost) use ($today): void {
                    $user = $fixedCost->user;

                    if ($user === null) {
                        return;
                    }

                    $dueDate = $fixedCost->next_booking_date?->toImmutable()->startOfDay();

                    if ($dueDate === null) {
                        return;
                    }

                    $fixedCost->reminders
                        ->filter(fn(FixedCostReminder $reminder): bool => $this->shouldSendReminder($reminder, $today, $dueDate))
                        ->each(function (FixedCostReminder $reminder) use ($user, $fixedCost, $dueDate): void {
                            if (!$this->hasDeliveryChannel($user, $reminder)) {
                                return;
                            }

                            $user->notify(new FixedCostReminderNotification($fixedCost, $reminder));

                            $reminder->forceFill([
                                FixedCostReminder::last_sent_booking_date => $dueDate->toDateString(),
                            ])->save();
                        });
                });
        } catch (\Throwable $e) {
            $this->fail($e);
            Log::error('Error sending upcoming fixed costs reminder: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function shouldSendReminder(FixedCostReminder $reminder, CarbonImmutable $today, CarbonImmutable $dueDate): bool
    {
        if (!$reminder->{FixedCostReminder::enabled}) {
            return false;
        }

        if ((int)$reminder->{FixedCostReminder::days_before} < 0) {
            return false;
        }

        if ($reminder->{FixedCostReminder::last_sent_booking_date}?->toDateString() === $dueDate->toDateString()) {
            return false;
        }

        return $dueDate->subDays((int)$reminder->{FixedCostReminder::days_before})->isSameDay($today);
    }

    private function hasDeliveryChannel(User $user, FixedCostReminder $reminder): bool
    {
        $hasMailRecipient = filled($user->{User::email} ?? null);

        return ($reminder->{FixedCostReminder::send_mail} && $hasMailRecipient)
            || $reminder->{FixedCostReminder::send_notification};
    }
}


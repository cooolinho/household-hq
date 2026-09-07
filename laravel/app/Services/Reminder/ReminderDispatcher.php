<?php

namespace App\Services\Reminder;

use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Settings\ReminderSettings;
use Carbon\CarbonImmutable;

/**
 * Prüft alle aktiven Reminder auf Fälligkeit und verschickt die entsprechenden
 * Benachrichtigungen. Absichtlich ohne Queue-Abhängigkeit, damit der Job (der nur eine
 * dünne Hülle ist) und diese Logik unabhängig voneinander getestet werden können.
 */
readonly class ReminderDispatcher
{
    public function __construct(
        private ReminderDueCalculator $calculator,
        private ReminderSettings      $settings,
    )
    {
    }

    /**
     * @return int Anzahl der tatsächlich versendeten Erinnerungen
     */
    public function dispatchDue(CarbonImmutable $now): int
    {
        $sent = 0;
        $catchUpHours = max(0, $this->settings->catch_up_hours);
        $defaultRunAtTime = $this->settings->default_run_at_time;

        Reminder::query()
            ->where(Reminder::enabled, true)
            ->with([Reminder::belongs_to_user, Reminder::morph_to_remindable, Reminder::has_many_schedules])
            ->whereHas(Reminder::has_many_schedules, function ($query): void {
                $query->where(ReminderSchedule::enabled, true);
            })
            ->chunkById(100, function ($reminders) use ($now, $catchUpHours, $defaultRunAtTime, &$sent): void {
                foreach ($reminders as $reminder) {
                    $sent += $this->processReminder($reminder, $now, $catchUpHours, $defaultRunAtTime);
                }
            });

        return $sent;
    }

    private function processReminder(Reminder $reminder, CarbonImmutable $now, int $catchUpHours, string $defaultRunAtTime): int
    {
        $user = $reminder->{Reminder::belongs_to_user};

        if (!$user instanceof User) {
            return 0;
        }

        $isAnchored = $reminder->isAnchored();
        $anchor = $isAnchored ? $this->calculator->resolveAnchor($reminder) : null;

        if ($isAnchored && $anchor === null) {
            // Model, Property oder Wert nicht (mehr) vorhanden – nichts zu tun.
            return 0;
        }

        $sent = 0;

        foreach ($reminder->{Reminder::has_many_schedules} as $schedule) {
            if (!$schedule->{ReminderSchedule::enabled}) {
                continue;
            }

            $dueAt = $anchor !== null
                ? $this->processAnchoredSchedule($schedule, $anchor, $now, $catchUpHours, $defaultRunAtTime)
                : $this->processRecurringSchedule($schedule, $now, $catchUpHours, $defaultRunAtTime);

            if ($dueAt === null) {
                continue;
            }

            if (!$this->hasDeliveryChannel($user, $reminder)) {
                continue;
            }

            $user->notify(new ReminderNotification($reminder, $schedule, $dueAt));
            $sent++;
        }

        return $sent;
    }

    /**
     * @return CarbonImmutable|null Fälligkeitszeitpunkt, wenn versendet werden soll, sonst null.
     */
    private function processAnchoredSchedule(
        ReminderSchedule $schedule,
        CarbonImmutable  $anchor,
        CarbonImmutable  $now,
        int              $catchUpHours,
        string           $defaultRunAtTime,
    ): ?CarbonImmutable
    {
        $dueAt = $this->calculator->resolveOffsetDueAt($anchor, $schedule, $defaultRunAtTime);

        if ($dueAt === null) {
            return null;
        }

        if (!$schedule->{ReminderSchedule::next_due_at}?->equalTo($dueAt)) {
            $schedule->{ReminderSchedule::next_due_at} = $dueAt;
            $schedule->save();
        }

        $alreadySent = $schedule->{ReminderSchedule::last_sent_for}?->equalTo($anchor) ?? false;

        if ($alreadySent) {
            return null;
        }

        $withinCatchUpWindow = $now->greaterThanOrEqualTo($dueAt)
            && $now->lessThanOrEqualTo($dueAt->addHours($catchUpHours));
        $anchorNotYetPassed = $now->lessThanOrEqualTo($anchor->endOfDay());

        if (!$withinCatchUpWindow || !$anchorNotYetPassed) {
            return null;
        }

        $schedule->{ReminderSchedule::last_sent_for} = $anchor;
        $schedule->{ReminderSchedule::last_sent_at} = $now;
        $schedule->save();

        return $dueAt;
    }

    /**
     * @return CarbonImmutable|null Fälligkeitszeitpunkt, wenn versendet werden soll, sonst null.
     */
    private function processRecurringSchedule(
        ReminderSchedule $schedule,
        CarbonImmutable  $now,
        int              $catchUpHours,
        string           $defaultRunAtTime,
    ): ?CarbonImmutable
    {
        if ($schedule->{ReminderSchedule::next_due_at} === null) {
            // Erstberechnung: Der allererste Termin der Wiederholungsregel, unabhängig von
            // "jetzt". Ob er noch fällig ist, entscheidet der Vergleich mit $now weiter unten.
            $schedule->{ReminderSchedule::next_due_at} = $this->calculator->resolveNextRecurrence(
                $schedule,
                self::epoch(),
                $defaultRunAtTime,
            );
        }

        $dueAt = $schedule->{ReminderSchedule::next_due_at};

        if ($dueAt === null) {
            $schedule->save();

            return null;
        }

        if ($now->lessThan($dueAt)) {
            // Noch nicht fällig.
            $schedule->save();

            return null;
        }

        if ($now->greaterThan($dueAt->addHours($catchUpHours))) {
            // Kulanzfenster verstrichen, ohne dass versendet wurde – zum nächsten zukünftigen
            // Termin vorspulen, damit die Erinnerung nicht dauerhaft an einem alten Termin hängt.
            $schedule->{ReminderSchedule::next_due_at} = $this->calculator->resolveNextRecurrence($schedule, $now, $defaultRunAtTime);
            $schedule->save();

            return null;
        }

        $schedule->{ReminderSchedule::last_sent_for} = $dueAt;
        $schedule->{ReminderSchedule::last_sent_at} = $now;
        $schedule->{ReminderSchedule::next_due_at} = $this->calculator->resolveNextRecurrence($schedule, $dueAt, $defaultRunAtTime);
        $schedule->save();

        return $dueAt;
    }

    /**
     * Sentinel weit in der Vergangenheit, damit resolveNextRecurrence() den allerersten
     * Termin einer Wiederholungsregel liefert statt eines Termins relativ zu "jetzt".
     */
    private static function epoch(): CarbonImmutable
    {
        return CarbonImmutable::createFromDate(1970, 1, 1)->startOfDay();
    }

    private function hasDeliveryChannel(User $user, Reminder $reminder): bool
    {
        $hasMailRecipient = filled($user->{User::email} ?? null);

        return ($reminder->{Reminder::send_mail} && $hasMailRecipient)
            || $reminder->{Reminder::send_notification};
    }
}

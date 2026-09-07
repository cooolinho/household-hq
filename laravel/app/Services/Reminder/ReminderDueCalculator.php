<?php

namespace App\Services\Reminder;

use App\Models\Enums\ReminderOffsetUnitEnum;
use App\Models\Enums\ReminderRecurrenceEnum;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Berechnet Fälligkeitstermine für Reminder-Intervalle: entweder als Vorlauf vor einem
 * Ankerdatum (Model-gebundener Reminder) oder als nächster Termin einer Wiederholungsregel
 * (freier Reminder).
 */
readonly class ReminderDueCalculator
{
    public function __construct(private ReminderTargetRegistry $registry)
    {
    }

    /**
     * Liest die konfigurierte Datums-Property vom remindable-Model und liefert sie als
     * Tagesanfang zurück. Null, wenn Model, Property oder Wert fehlen.
     */
    public function resolveAnchor(Reminder $reminder): ?CarbonImmutable
    {
        $model = $reminder->{Reminder::morph_to_remindable};
        $property = $reminder->{Reminder::date_property};

        if ($model === null || $property === null) {
            return null;
        }

        $value = $model->{$property} ?? null;

        return $this->normalizeDate($value);
    }

    public function normalizeDate(mixed $value): ?CarbonImmutable
    {
        $date = match (true) {
            $value instanceof CarbonImmutable => $value,
            $value instanceof CarbonInterface => CarbonImmutable::instance($value),
            is_string($value) && $value !== '' => CarbonImmutable::parse($value),
            default => null,
        };

        return $date?->startOfDay();
    }

    /**
     * Fälligkeitszeitpunkt eines Vorlauf-Intervalls relativ zum Ankerdatum.
     */
    public function resolveOffsetDueAt(CarbonImmutable $anchor, ReminderSchedule $schedule, string $defaultRunAtTime): ?CarbonImmutable
    {
        $unit = ReminderOffsetUnitEnum::tryFrom((string)$schedule->{ReminderSchedule::offset_unit});
        $value = $schedule->{ReminderSchedule::offset_value};

        if ($unit === null || $value === null) {
            return null;
        }

        $dueAt = $unit->subtract($anchor, (int)$value);

        if ($unit === ReminderOffsetUnitEnum::HOUR) {
            return $dueAt;
        }

        return $this->applyRunAtTime($dueAt, $schedule->{ReminderSchedule::run_at_time}, $defaultRunAtTime);
    }

    private function applyRunAtTime(CarbonImmutable $date, ?string $runAtTime, string $defaultRunAtTime): CarbonImmutable
    {
        $time = $runAtTime ?? $defaultRunAtTime;
        [$hour, $minute] = array_pad(explode(':', $time), 2, 0);

        return $date->setTime((int)$hour, (int)$minute);
    }

    /**
     * Nächster Fälligkeitstermin einer Wiederholungsregel nach $after.
     */
    public function resolveNextRecurrence(ReminderSchedule $schedule, CarbonImmutable $after, string $defaultRunAtTime): ?CarbonImmutable
    {
        $recurrence = ReminderRecurrenceEnum::tryFrom((string)$schedule->{ReminderSchedule::recurrence});

        if ($recurrence === null) {
            return null;
        }

        $runAtTime = $schedule->{ReminderSchedule::run_at_time} ?? $defaultRunAtTime;
        $base = $schedule->{ReminderSchedule::start_date}?->toImmutable()->startOfDay()
            ?? $schedule->{ReminderSchedule::created_at}?->toImmutable()->startOfDay()
            ?? $after;

        if ($recurrence === ReminderRecurrenceEnum::ONCE) {
            $due = $this->applyRunAtTime($base, null, $runAtTime);

            return $due->greaterThan($after) ? $due : null;
        }

        $candidate = $this->applyRunAtTime($base, null, $runAtTime);

        if ($recurrence === ReminderRecurrenceEnum::MONTHLY_ON_DAY) {
            $day = max(1, min(31, (int)($schedule->{ReminderSchedule::recurrence_value} ?? 1)));
            $candidate = $candidate->day(min($day, $candidate->daysInMonth));
        }

        // Solange vorwärts schieben, bis der Termin nach $after liegt. Der Guard verhindert eine
        // Endlosschleife für sehr alte Startdaten.
        $guard = 0;
        while ($candidate->lessThanOrEqualTo($after) && $guard < 10000) {
            $candidate = $this->shiftRecurrence($recurrence, $candidate, (int)($schedule->{ReminderSchedule::recurrence_value} ?? 1));
            $guard++;
        }

        return $candidate;
    }

    private function shiftRecurrence(ReminderRecurrenceEnum $recurrence, CarbonImmutable $date, int $value): CarbonImmutable
    {
        return match ($recurrence) {
            ReminderRecurrenceEnum::DAILY => $date->addDay(),
            ReminderRecurrenceEnum::EVERY_N_DAYS => $date->addDays(max(1, $value)),
            ReminderRecurrenceEnum::WEEKLY => $date->addWeek(),
            ReminderRecurrenceEnum::EVERY_N_WEEKS => $date->addWeeks(max(1, $value)),
            ReminderRecurrenceEnum::MONTHLY_ON_DAY => $this->nextMonthlyOnDay($date, $value),
            ReminderRecurrenceEnum::EVERY_N_MONTHS => $date->addMonths(max(1, $value)),
            ReminderRecurrenceEnum::QUARTERLY => $date->addMonths(3),
            ReminderRecurrenceEnum::HALF_YEARLY => $date->addMonths(6),
            ReminderRecurrenceEnum::YEARLY => $date->addYear(),
            ReminderRecurrenceEnum::ONCE => $date,
        };
    }

    /**
     * Nächster Monat mit demselben Tag (Tag > Monatslänge wird auf den letzten Tag geklemmt),
     * z. B. Tag 31 im Februar => letzter Tag des Februars.
     */
    private function nextMonthlyOnDay(CarbonImmutable $date, int $day): CarbonImmutable
    {
        $day = max(1, min(31, $day));
        // startOfMonth() would reset the time-of-day, so it's restored from $date afterwards.
        $next = $date->addMonthNoOverflow()->startOfMonth()->setTimeFrom($date);

        return $next->addDays(min($day, $next->daysInMonth) - 1);
    }
}

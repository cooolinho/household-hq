<?php

namespace App\Services;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class FixedCostNextBookingDateCalculator
{
    public function resolveNextBookingDate(FixedCost $fixedCost, CarbonImmutable $today): ?CarbonImmutable
    {
        $referenceDate = $fixedCost->next_booking_date?->toImmutable()->startOfDay()
            ?? $fixedCost->created_at?->toImmutable()->startOfDay()
            ?? $today;

        $candidate = $referenceDate;
        if ($candidate->greaterThan($today)) {
            return $this->isAllowedByEndMode($fixedCost, $candidate) ? $candidate : null;
        }

        // Catch up the next booking date until it is strictly in the future.
        while ($candidate->lessThanOrEqualTo($today)) {
            $candidate = $this->addInterval($fixedCost, $candidate);

            if (!$this->isAllowedByEndMode($fixedCost, $candidate)) {
                return null;
            }
        }

        return $candidate;
    }

    public function addInterval(FixedCost $fixedCost, CarbonImmutable $date): CarbonImmutable
    {
        return $this->shiftDate($date, $this->resolveIntervalDefinition($fixedCost, $date), 1);
    }

    public function subtractInterval(FixedCost $fixedCost, CarbonImmutable $date): CarbonImmutable
    {
        return $this->shiftDate($date, $this->resolveIntervalDefinition($fixedCost, $date), -1);
    }

    public function isBookingDateAllowed(FixedCost $fixedCost, CarbonImmutable $date): bool
    {
        return $this->isAllowedByEndMode($fixedCost, $date);
    }

    private function isAllowedByEndMode(FixedCost $fixedCost, CarbonImmutable $candidate): bool
    {
        if ($fixedCost->ends_mode !== FixedCostEndsModeEnum::ENDS->name) {
            return true;
        }

        if ($fixedCost->ends_date === null) {
            return true;
        }

        return $candidate->lessThanOrEqualTo($fixedCost->ends_date->toImmutable()->startOfDay());
    }

    /**
     * @return array{unit: string, value: int}
     */
    private function resolveIntervalDefinition(FixedCost $fixedCost, CarbonImmutable $date): array
    {
        $isExtended = $fixedCost->ends_mode === FixedCostEndsModeEnum::EXTENDED->name
            && $fixedCost->extended_date !== null
            && $fixedCost->extended_interval !== null
            && $date->greaterThanOrEqualTo($fixedCost->extended_date->toImmutable()->startOfDay());
        $interval = $isExtended
            ? $fixedCost->extended_interval
            : $fixedCost->interval;

        return match ($interval) {
            FixedCostIntervalEnum::WEEKLY->name => ['unit' => FixedCostIntervalUnitEnum::WEEK->name, 'value' => 1],
            FixedCostIntervalEnum::TWO_WEEKS->name => ['unit' => FixedCostIntervalUnitEnum::WEEK->name, 'value' => 2],
            FixedCostIntervalEnum::MONTHLY->name => ['unit' => FixedCostIntervalUnitEnum::MONTH->name, 'value' => 1],
            FixedCostIntervalEnum::TWO_MONTHS->name => ['unit' => FixedCostIntervalUnitEnum::MONTH->name, 'value' => 2],
            FixedCostIntervalEnum::QUARTERLY->name => ['unit' => FixedCostIntervalUnitEnum::MONTH->name, 'value' => 3],
            FixedCostIntervalEnum::HALF_YEARLY->name => ['unit' => FixedCostIntervalUnitEnum::MONTH->name, 'value' => 6],
            FixedCostIntervalEnum::YEARLY->name => ['unit' => FixedCostIntervalUnitEnum::YEAR->name, 'value' => 1],
            FixedCostIntervalEnum::CUSTOM->name => $this->resolveCustomIntervalDefinition($fixedCost, $isExtended),
            default => throw new InvalidArgumentException(sprintf(
                'Unbekanntes Fixkostenintervall "%s".',
                (string)$interval,
            )),
        };
    }

    /**
     * @return array{unit: string, value: int}
     */
    private function resolveCustomIntervalDefinition(FixedCost $fixedCost, bool $isExtended): array
    {
        $value = $isExtended
            ? $fixedCost->custom_extended_interval_value
            : $fixedCost->custom_interval_value;
        $unit = $isExtended
            ? $fixedCost->custom_extended_interval_unit
            : $fixedCost->custom_interval_unit;

        if (!is_int($value) || $value < 1 || !is_string($unit)) {
            throw new InvalidArgumentException('Custom-Fixkostenintervalle benötigen einen positiven Wert und eine Einheit.');
        }

        if (!FixedCostIntervalUnitEnum::tryFrom($unit)) {
            throw new InvalidArgumentException(sprintf(
                'Unbekannte Custom-Fixkostenintervalleinheit "%s".',
                $unit,
            ));
        }

        return [
            'unit' => $unit,
            'value' => $value,
        ];
    }

    /**
     * @param array{unit: string, value: int} $definition
     */
    private function shiftDate(CarbonImmutable $date, array $definition, int $direction): CarbonImmutable
    {
        $amount = $definition['value'] * $direction;

        return match ($definition['unit']) {
            FixedCostIntervalUnitEnum::DAY->name => $amount >= 0
                ? $date->addDays($amount)
                : $date->subDays(abs($amount)),
            FixedCostIntervalUnitEnum::WEEK->name => $amount >= 0
                ? $date->addWeeks($amount)
                : $date->subWeeks(abs($amount)),
            FixedCostIntervalUnitEnum::MONTH->name => $amount >= 0
                ? $date->addMonths($amount)
                : $date->subMonths(abs($amount)),
            FixedCostIntervalUnitEnum::YEAR->name => $amount >= 0
                ? $date->addYears($amount)
                : $date->subYears(abs($amount)),
            default => throw new InvalidArgumentException(sprintf(
                'Unbekannte Intervall-Einheit "%s".',
                $definition['unit'],
            )),
        };
    }
}

<?php

namespace App\Services\FixedCost;

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;

/**
 * Rechnet Fixkostenbeträge auf Monats- bzw. Jahresbasis um.
 *
 * Bewusst fehlertolerant: Ein unvollständig konfiguriertes CUSTOM-Intervall liefert
 * 0.0 statt einer Exception, damit ein einzelner Datensatz nicht das komplette
 * Widget- bzw. Seiten-Rendering sprengt. Ungültige Datensätze werden über
 * isConfigurationValid() erkannt und im UI als Warnung ausgewiesen.
 */
final class FixedCostAmountNormalizer
{
    public function occurrencesPerYear(FixedCost $fixedCost): ?float
    {
        $interval = FixedCostIntervalEnum::tryFrom((string)$fixedCost->{FixedCost::interval});

        if ($interval === null) {
            return null;
        }

        if ($interval !== FixedCostIntervalEnum::CUSTOM) {
            return $interval->occurrencesPerYear();
        }

        $value = $fixedCost->{FixedCost::custom_interval_value};
        $unit = FixedCostIntervalUnitEnum::tryFrom((string)$fixedCost->{FixedCost::custom_interval_unit});

        if (!is_int($value) || $value < 1 || $unit === null) {
            return null;
        }

        return $unit->occurrencesPerYear() / $value;
    }

    public function isConfigurationValid(FixedCost $fixedCost): bool
    {
        return $this->occurrencesPerYear($fixedCost) !== null;
    }

    public function yearlyFactor(FixedCost $fixedCost): float
    {
        return $this->occurrencesPerYear($fixedCost) ?? 0.0;
    }

    public function monthlyFactor(FixedCost $fixedCost): float
    {
        return $this->yearlyFactor($fixedCost) / 12.0;
    }

    /** Betrag als positive Monatsbelastung bzw. -einnahme. */
    public function monthlyAmount(FixedCost $fixedCost): float
    {
        return abs((float)$fixedCost->{FixedCost::amount}) * $this->monthlyFactor($fixedCost);
    }

    public function yearlyAmount(FixedCost $fixedCost): float
    {
        return abs((float)$fixedCost->{FixedCost::amount}) * $this->yearlyFactor($fixedCost);
    }
}

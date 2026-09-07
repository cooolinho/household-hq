<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Widgets;

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class FixedCostsDashboardWidget extends Widget
{
    public int|string|array $columnSpan = 'full';

    /** Anzahl der Tage, die für "bevorstehende Buchungen" vorausgeschaut werden. */
    public int $lookaheadDays = 30;

    protected string $view = 'filament.app.resources.financial.fixed-costs.widgets.fixed-costs-dashboard-widget';

    protected function getViewData(): array
    {
        $today = CarbonImmutable::today();
        $userId = auth()->id();

        /** @var Collection<int, FixedCost> $allActive */
        $allActive = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->get();

        $incomes = $allActive->filter(fn(FixedCost $fc) => $fc->amount > 0);
        $expenses = $allActive->filter(fn(FixedCost $fc) => $fc->amount < 0);

        $monthlyIncome = $incomes->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->monthlyFactor($fc));
        $monthlyExpenses = $expenses->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->monthlyFactor($fc));
        $monthlyBalance = $monthlyIncome - $monthlyExpenses;

        $yearlyIncome = $incomes->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->yearlyFactor($fc));
        $yearlyExpenses = $expenses->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->yearlyFactor($fc));
        $yearlyBalance = $yearlyIncome - $yearlyExpenses;

        // ── Wöchentliche Übersicht (nächste 7 Tage) ──────────────────
        $weekEnd = $today->addDays(7);

        $weeklyAll = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $weekEnd->toDateString()])
            ->orderBy(FixedCost::next_booking_date)
            ->get();

        $weeklyIncome = $weeklyAll->filter(fn(FixedCost $fc) => $fc->amount > 0);
        $weeklyExpenses = $weeklyAll->filter(fn(FixedCost $fc) => $fc->amount < 0);

        // ── Lookahead-Übersicht (konfigurierbar, Standard 30 Tage) ──
        $lookaheadEnd = $today->addDays($this->lookaheadDays);

        $upcomingAll = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $lookaheadEnd->toDateString()])
            ->orderBy(FixedCost::next_booking_date)
            ->get();

        $upcomingIncome = $upcomingAll->filter(fn(FixedCost $fc) => $fc->amount > 0);
        $upcomingExpenses = $upcomingAll->filter(fn(FixedCost $fc) => $fc->amount < 0);

        return [
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpenses' => $monthlyExpenses,
            'monthlyBalance' => $monthlyBalance,
            'yearlyIncome' => $yearlyIncome,
            'yearlyExpenses' => $yearlyExpenses,
            'yearlyBalance' => $yearlyBalance,
            // wöchentlich
            'weeklyAll' => $weeklyAll,
            'weeklyIncome' => $weeklyIncome,
            'weeklyExpenses' => $weeklyExpenses,
            'weeklyIncomeTotal' => $weeklyIncome->sum(fn(FixedCost $fc) => abs($fc->amount)),
            'weeklyExpensesTotal' => $weeklyExpenses->sum(fn(FixedCost $fc) => abs($fc->amount)),
            'weeklyBalance' => $weeklyIncome->sum('amount') + $weeklyExpenses->sum('amount'),
            // lookahead
            'upcomingIncome' => $upcomingIncome,
            'upcomingExpenses' => $upcomingExpenses,
            'upcomingIncomeTotal' => $upcomingIncome->sum(fn(FixedCost $fc) => abs($fc->amount)),
            'upcomingExpensesTotal' => $upcomingExpenses->sum(fn(FixedCost $fc) => abs($fc->amount)),
            'lookaheadDays' => $this->lookaheadDays,
            'today' => $today,
        ];
    }

    /** Faktor zur Umrechnung auf Monatsbasis. */
    private function monthlyFactor(FixedCost $fixedCost): float
    {
        $interval = (string)$fixedCost->{FixedCost::interval};

        if ($interval === FixedCostIntervalEnum::CUSTOM->name) {
            return $this->customMonthlyFactor($fixedCost);
        }

        return match ($interval) {
            'WEEKLY' => 52 / 12,
            'TWO_WEEKS' => 26 / 12,
            'MONTHLY' => 1.0,
            'TWO_MONTHS' => 6 / 12,
            'QUARTERLY' => 4 / 12,
            'HALF_YEARLY' => 2 / 12,
            'YEARLY' => 1 / 12,
            default => 1.0,
        };
    }

    /** Faktor zur Umrechnung auf Jahresbasis. */
    private function yearlyFactor(FixedCost $fixedCost): float
    {
        $interval = (string)$fixedCost->{FixedCost::interval};

        if ($interval === FixedCostIntervalEnum::CUSTOM->name) {
            return $this->customYearlyFactor($fixedCost);
        }

        return match ($interval) {
            'WEEKLY' => 52.0,
            'TWO_WEEKS' => 26.0,
            'MONTHLY' => 12.0,
            'TWO_MONTHS' => 6.0,
            'QUARTERLY' => 4.0,
            'HALF_YEARLY' => 2.0,
            'YEARLY' => 1.0,
            default => 12.0,
        };
    }

    private function customMonthlyFactor(FixedCost $fixedCost): float
    {
        [$value, $unit] = $this->customIntervalValues($fixedCost);

        return match ($unit) {
            FixedCostIntervalUnitEnum::DAY => 365.25 / 12 / $value,
            FixedCostIntervalUnitEnum::WEEK => 52 / 12 / $value,
            FixedCostIntervalUnitEnum::MONTH => 1 / $value,
            FixedCostIntervalUnitEnum::YEAR => 1 / (12 * $value),
        };
    }

    private function customYearlyFactor(FixedCost $fixedCost): float
    {
        [$value, $unit] = $this->customIntervalValues($fixedCost);

        return match ($unit) {
            FixedCostIntervalUnitEnum::DAY => 365.25 / $value,
            FixedCostIntervalUnitEnum::WEEK => 52 / $value,
            FixedCostIntervalUnitEnum::MONTH => 12 / $value,
            FixedCostIntervalUnitEnum::YEAR => 1 / $value,
        };
    }

    /**
     * @return array{0: int, 1: FixedCostIntervalUnitEnum}
     */
    private function customIntervalValues(FixedCost $fixedCost): array
    {
        $value = $fixedCost->{FixedCost::custom_interval_value};
        $unit = FixedCostIntervalUnitEnum::tryFrom((string)$fixedCost->{FixedCost::custom_interval_unit});

        if (!is_int($value) || $value < 1 || $unit === null) {
            throw new InvalidArgumentException('Custom-Fixkostenintervalle benötigen einen positiven Wert und eine gültige Einheit.');
        }

        return [$value, $unit];
    }
}

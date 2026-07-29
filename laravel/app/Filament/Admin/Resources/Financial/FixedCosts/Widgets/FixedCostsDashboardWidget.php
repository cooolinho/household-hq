<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Widgets;

use App\Models\Financial\FixedCost;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class FixedCostsDashboardWidget extends Widget
{
    public int|string|array $columnSpan = 'full';
    /** Anzahl der Tage, die für "bevorstehende Buchungen" vorausgeschaut werden. */
    public int $lookaheadDays = 30;
    protected string $view = 'filament.admin.resources.financial.fixed-costs.widgets.fixed-costs-dashboard-widget';

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

        $monthlyIncome = $incomes->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->monthlyFactor($fc->interval));
        $monthlyExpenses = $expenses->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->monthlyFactor($fc->interval));
        $monthlyBalance = $monthlyIncome - $monthlyExpenses;

        $yearlyIncome = $incomes->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->yearlyFactor($fc->interval));
        $yearlyExpenses = $expenses->sum(fn(FixedCost $fc) => abs($fc->amount) * $this->yearlyFactor($fc->interval));
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
    private function monthlyFactor(?string $interval): float
    {
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
    private function yearlyFactor(?string $interval): float
    {
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
}

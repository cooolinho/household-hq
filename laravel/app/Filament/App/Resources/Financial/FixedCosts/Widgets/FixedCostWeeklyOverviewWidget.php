<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Widgets;

use App\Models\Financial\FixedCost;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Übersicht der in den nächsten 7 Tagen fälligen Fixkosten-Buchungen.
 */
class FixedCostWeeklyOverviewWidget extends Widget
{
    public int|string|array $columnSpan = 'full';

    protected string $view = 'filament.app.resources.financial.fixed-costs.widgets.fixed-cost-weekly-overview-widget';

    protected function getViewData(): array
    {
        $today = CarbonImmutable::today();
        $userId = auth()->id();
        $weekEnd = $today->addDays(7);

        /** @var Collection<int, FixedCost> $weeklyAll */
        $weeklyAll = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $weekEnd->toDateString()])
            ->orderBy(FixedCost::next_booking_date)
            ->get();

        $weeklyIncome = $weeklyAll->filter(fn(FixedCost $fc) => $fc->amount > 0);
        $weeklyExpenses = $weeklyAll->filter(fn(FixedCost $fc) => $fc->amount < 0);

        $weeklyIncomeTotal = (float)$weeklyIncome->sum(fn(FixedCost $fc) => abs($fc->amount));
        $weeklyExpensesTotal = (float)$weeklyExpenses->sum(fn(FixedCost $fc) => abs($fc->amount));

        return [
            'today' => $today,
            'weeklyAll' => $weeklyAll,
            'weeklyIncome' => $weeklyIncome,
            'weeklyExpenses' => $weeklyExpenses,
            'weeklyIncomeTotal' => $weeklyIncomeTotal,
            'weeklyExpensesTotal' => $weeklyExpensesTotal,
            'weeklyBalance' => $weeklyIncomeTotal - $weeklyExpensesTotal,
        ];
    }
}

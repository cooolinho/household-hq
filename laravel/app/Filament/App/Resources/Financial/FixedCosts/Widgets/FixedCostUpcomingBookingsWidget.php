<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Widgets;

use App\Models\Financial\FixedCost;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Bevorstehende Fixkosten-Buchungen in einem konfigurierbaren Zeitfenster (Standard 30 Tage).
 */
class FixedCostUpcomingBookingsWidget extends Widget
{
    public int|string|array $columnSpan = 'full';

    public int $lookaheadDays = 30;

    protected string $view = 'filament.app.resources.financial.fixed-costs.widgets.fixed-cost-upcoming-bookings-widget';

    protected function getViewData(): array
    {
        $today = CarbonImmutable::today();
        $userId = auth()->id();
        $lookaheadEnd = $today->addDays($this->lookaheadDays);

        /** @var Collection<int, FixedCost> $upcomingAll */
        $upcomingAll = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $lookaheadEnd->toDateString()])
            ->orderBy(FixedCost::next_booking_date)
            ->get();

        $upcomingIncome = $upcomingAll->filter(fn(FixedCost $fc) => $fc->amount > 0);
        $upcomingExpenses = $upcomingAll->filter(fn(FixedCost $fc) => $fc->amount < 0);

        return [
            'today' => $today,
            'lookaheadDays' => $this->lookaheadDays,
            'upcomingIncome' => $upcomingIncome,
            'upcomingExpenses' => $upcomingExpenses,
            'upcomingIncomeTotal' => (float)$upcomingIncome->sum(fn(FixedCost $fc) => abs($fc->amount)),
            'upcomingExpensesTotal' => (float)$upcomingExpenses->sum(fn(FixedCost $fc) => abs($fc->amount)),
        ];
    }
}

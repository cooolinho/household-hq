<?php

namespace App\Filament\App\Widgets\FixedCostStatistics\Concerns;

use App\Filament\App\Pages\FixedCostStatisticsPage;
use App\Models\DashboardWidgetPreference;
use App\Models\Enums\FixedCostStatisticsPeriodEnum;
use App\Services\FixedCost\FixedCostBalanceService;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Throwable;

/**
 * Liest den auf der Statistikseite gewählten Zeitraum und den Budget-Schalter aus $pageFilters.
 *
 * $pageFilters kommt unvalidiert aus der Filter-Form (Filament-Dokumentation), deshalb wird
 * hier defensiv geparst: unbekannte/fehlende Werte fallen auf den Default zurück, vertauschte
 * Grenzen werden getauscht, und die Spanne wird auf maximal 60 Monate geklemmt – das ist die
 * primäre Absicherung gegen eine ausufernde Buchungsprojektion bei einem absurden Zeitraum.
 */
trait InteractsWithFixedCostStatisticsFilters
{
    use InteractsWithPageFilters;

    private const int MAX_RANGE_MONTHS = 60;

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    protected function resolveRange(): array
    {
        $today = CarbonImmutable::today();
        $preset = FixedCostStatisticsPeriodEnum::tryFrom(
            (string)($this->pageFilters[FixedCostStatisticsPage::FILTER_PRESET] ?? ''),
        ) ?? FixedCostStatisticsPeriodEnum::tryFrom(FixedCostStatisticsPeriodEnum::default());

        $range = $preset->isCustom()
            ? $this->resolveCustomRange($today)
            : $preset->resolveRange($today);

        $start = $range['start']->startOfMonth()->startOfDay();
        $end = $range['end']->endOfMonth()->endOfDay();

        if ($start->diffInMonths($end) >= self::MAX_RANGE_MONTHS) {
            $start = $end->startOfMonth()->subMonths(self::MAX_RANGE_MONTHS - 1)->startOfDay();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function resolveCustomRange(CarbonImmutable $today): array
    {
        $fallback = FixedCostStatisticsPeriodEnum::LAST_6_MONTHS->resolveRange($today);

        $fromRaw = $this->pageFilters[FixedCostStatisticsPage::FILTER_FROM] ?? null;
        $toRaw = $this->pageFilters[FixedCostStatisticsPage::FILTER_TO] ?? null;

        if (!is_string($fromRaw) || !is_string($toRaw) || $fromRaw === '' || $toRaw === '') {
            return $fallback;
        }

        try {
            $from = CarbonImmutable::parse($fromRaw)->startOfDay();
            $to = CarbonImmutable::parse($toRaw)->startOfDay();
        } catch (Throwable) {
            return $fallback;
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return ['start' => $from, 'end' => $to];
    }

    protected function includeBudgets(): bool
    {
        $filters = $this->pageFilters ?? [];

        if (array_key_exists(FixedCostStatisticsPage::FILTER_INCLUDE_BUDGETS, $filters)) {
            return (bool)$filters[FixedCostStatisticsPage::FILTER_INCLUDE_BUDGETS];
        }

        return (bool)DashboardWidgetPreference::forUser((int)auth()->id())
            ->{DashboardWidgetPreference::include_budgets_in_balance};
    }

    protected function balanceService(): FixedCostBalanceService
    {
        return app(FixedCostBalanceService::class);
    }
}

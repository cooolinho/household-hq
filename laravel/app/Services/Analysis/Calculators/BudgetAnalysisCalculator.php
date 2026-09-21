<?php

namespace App\Services\Analysis\Calculators;

use App\Models\AnalysisCard;
use App\Models\Enums\AnalysisBudgetIntervalEnum;
use App\Models\Financial\Budget;
use App\Services\Analysis\AnalysisConfiguration;
use App\Services\Analysis\DTO\AnalysisCardData;
use App\Services\Analysis\DTO\AnalysisDetailData;
use App\Services\Budget\BudgetCalculation;
use App\Services\Budget\BudgetCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Modul "Budgets": Fortschritts-Balken je Budget (ausgeg./Limit/%) + Summe.
 *
 * Delegiert die Einzel-Berechnung an den bestehenden BudgetCalculationService.
 */
final class BudgetAnalysisCalculator extends AbstractAnalysisCalculator
{
    private const int MAX_BUDGETS_ON_CARD = 9;

    public function card(AnalysisCard $card): AnalysisCardData
    {
        $config = $this->config($card);
        $userId = (int) $card->{AnalysisCard::user_id};
        $interval = $config->budgetInterval;
        $currency = $config->currency;
        $budgets = $this->budgetsForCard($userId, $config);

        $reference = CarbonImmutable::now();
        $service = app(BudgetCalculationService::class);
        $list = [];
        $totalSpent = 0.0;
        $totalLimit = 0.0;

        foreach ($budgets as $budget) {
            $budget->currency = $budget->{Budget::currency} ?? $currency;

            if ($interval === AnalysisBudgetIntervalEnum::ALL) {
                $start = CarbonImmutable::parse($budget->created_at)->startOfDay();
                $end = $reference->endOfDay();
                $limit = (float) $budget->{Budget::amount};
                $spent = $service->spentAmount($budget, $start, $end);
                $percentage = $limit > 0.0 ? $spent / $limit * 100 : 0.0;
                $status = $service->resolveStatus($budget, $percentage);
            } else {
                $calc = $this->calculateForInterval($budget, $reference, $interval, $service);
                $spent = $calc->spent;
                $limit = $calc->limit;
                $percentage = $calc->percentage;
                $status = $calc->status;
            }

            $list[] = [
                'name' => (string) $budget->{Budget::name},
                'spent' => $this->formatMoney($spent, $currency),
                'limit' => $this->formatMoney($limit, $currency),
                'percentage' => $percentage,
                'progress' => min(100.0, max(0.0, $percentage)),
                'remaining' => $this->formatMoney($limit - $spent, $currency),
                'status' => $status->label(),
                'status_color' => $status->color(),
            ];

            $totalSpent += $spent;
            $totalLimit += $limit;
        }

        return new AnalysisCardData(
            title: (string) $card->{AnalysisCard::title},
            module: $card->{AnalysisCard::module},
            totalLabel: 'Summe Budgets',
            totalFormatted: $this->formatMoney($totalSpent, $currency).' / '.$this->formatMoney($totalLimit, $currency),
            chartType: null,
            chartLabels: [],
            chartDatasets: [],
            list: $list,
        );
    }

    public function detail(AnalysisCard $card): AnalysisDetailData
    {
        $config = $this->config($card);
        $userId = (int) $card->{AnalysisCard::user_id};
        $interval = $config->budgetInterval;
        $currency = $config->currency;
        $budgets = $this->budgetsForCard($userId, $config);
        $reference = CarbonImmutable::now();
        $service = app(BudgetCalculationService::class);

        $rows = [];
        $labels = [];
        $spentData = [];
        $limitData = [];
        $totalSpent = 0.0;
        $totalLimit = 0.0;

        foreach ($budgets as $budget) {
            $calc = $interval === AnalysisBudgetIntervalEnum::ALL
                ? null
                : $this->calculateForInterval($budget, $reference, $interval, $service);

            if ($calc !== null) {
                $spent = $calc->spent;
                $limit = $calc->limit;
                $percentage = $calc->percentage;
                $status = $calc->status;
                $periodLabel = $calc->periodLabel();
            } else {
                $start = CarbonImmutable::parse($budget->created_at)->startOfDay();
                $end = $reference->endOfDay();
                $limit = (float) $budget->{Budget::amount};
                $spent = $service->spentAmount($budget, $start, $end);
                $percentage = $limit > 0.0 ? $spent / $limit * 100 : 0.0;
                $status = $service->resolveStatus($budget, $percentage);
                $periodLabel = 'Gesamtzeitraum';
            }

            $rows[] = [
                'name' => (string) $budget->{Budget::name},
                'period' => $periodLabel,
                'limit' => $this->formatMoney($limit, $currency),
                'spent' => $this->formatMoney($spent, $currency),
                'remaining' => $this->formatMoney($limit - $spent, $currency),
                'percent' => $this->formatPercent($percentage),
                'status' => $status->label(),
            ];

            $labels[] = (string) $budget->{Budget::name};
            $spentData[] = round($spent, 2);
            $limitData[] = round($limit, 2);
            $totalSpent += $spent;
            $totalLimit += $limit;
        }

        return new AnalysisDetailData(
            title: (string) $card->{AnalysisCard::title},
            chart: $labels === [] ? null : [
                'type' => 'bar',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Ausgegeben',
                        'data' => $spentData,
                        'backgroundColor' => $this->colors(count($spentData)),
                        'borderWidth' => 2,
                    ],
                    [
                        'label' => 'Limit',
                        'data' => $limitData,
                        'backgroundColor' => 'rgba(156, 163, 175, 0.3)',
                        'borderColor' => 'rgba(107, 114, 128, 0.9)',
                        'borderWidth' => 2,
                    ],
                ],
            ],
            stats: [
                ['label' => 'Verbraucht', 'value' => $this->formatMoney($totalSpent, $currency), 'color' => $totalSpent > $totalLimit ? 'danger' : 'success'],
                ['label' => 'Limit Gesamt', 'value' => $this->formatMoney($totalLimit, $currency)],
                ['label' => 'Verbleibend', 'value' => $this->formatMoney($totalLimit - $totalSpent, $currency)],
            ],
            rows: $rows,
            columns: [
                ['key' => 'name', 'label' => 'Budget'],
                ['key' => 'period', 'label' => 'Periode'],
                ['key' => 'limit', 'label' => 'Limit', 'align' => 'right'],
                ['key' => 'spent', 'label' => 'Ausgeg.', 'align' => 'right'],
                ['key' => 'remaining', 'label' => 'Verbl.', 'align' => 'right'],
                ['key' => 'percent', 'label' => '%', 'align' => 'right'],
                ['key' => 'status', 'label' => 'Status'],
            ],
        );
    }

    /**
     * @return Collection<int, Budget>
     */
    private function budgetsForCard(int $userId, AnalysisConfiguration $config): Collection
    {
        $query = Budget::query()->activeForUser($userId)->orderBy(Budget::id);

        if ($config->budgetIds !== []) {
            $query->whereIn(Budget::id, $config->budgetIds);
        }

        return $query->limit(self::MAX_BUDGETS_ON_CARD * 3)->with(Budget::belongs_to_many_transaction_categories)->get();
    }

    private function calculateForInterval(Budget $budget, CarbonImmutable $reference, ?AnalysisBudgetIntervalEnum $interval, BudgetCalculationService $service): BudgetCalculation
    {
        if ($interval === null || $interval === AnalysisBudgetIntervalEnum::ALL) {
            return $service->calculate($budget, $reference);
        }

        // Der Service rechnet immer auf Basis der Budget-Periode. Um das konfigurierte
        // Intervall zu berücksichtigen, überschreiben wir das Period-Range:
        $intervalStart = match ($interval) {
            AnalysisBudgetIntervalEnum::MONTHLY => $reference->startOfMonth(),
            AnalysisBudgetIntervalEnum::QUARTERLY => $reference->startOfQuarter(),
            AnalysisBudgetIntervalEnum::YEARLY => $reference->startOfYear(),
            // already handled ALL above
            default => $reference->startOfMonth(),
        };
        $intervalEnd = match ($interval) {
            AnalysisBudgetIntervalEnum::MONTHLY => $reference->endOfMonth(),
            AnalysisBudgetIntervalEnum::QUARTERLY => $reference->endOfQuarter(),
            AnalysisBudgetIntervalEnum::YEARLY => $reference->endOfYear(),
            default => $reference->endOfMonth(),
        };

        $limit = (float) $budget->{Budget::amount};
        $spent = $service->spentAmount($budget, $intervalStart, $intervalEnd);
        $percentage = $limit > 0.0 ? $spent / $limit * 100 : 0.0;
        $status = $service->resolveStatus($budget, $percentage);

        $daysTotal = (int) $intervalStart->diffInDays($intervalEnd->startOfDay()) + 1;
        $daysElapsed = min($daysTotal, max(1, (int) $intervalStart->diffInDays($reference->startOfDay()) + 1));
        $daysRemaining = max(0, $daysTotal - $daysElapsed);
        $dailyAverage = $spent / $daysElapsed;
        $projected = $dailyAverage * $daysTotal;
        $dailyAllowance = $daysRemaining > 0 ? max(0.0, ($limit - $spent)) / $daysRemaining : 0.0;

        $projectedExceededAt = null;
        if ($limit > 0 && $spent < $limit && $dailyAverage > 0) {
            $predicted = $intervalStart->addDays((int) ceil($limit / $dailyAverage) - 1)->startOfDay();
            $projectedExceededAt = $predicted->greaterThan($intervalEnd) ? null : $predicted;
        }

        return new BudgetCalculation(
            budget: $budget,
            periodStart: $intervalStart,
            periodEnd: $intervalEnd,
            limit: $limit,
            spent: $spent,
            remaining: $limit - $spent,
            percentage: $percentage,
            status: $status,
            daysTotal: $daysTotal,
            daysElapsed: $daysElapsed,
            daysRemaining: $daysRemaining,
            dailyAverage: $dailyAverage,
            dailyAllowance: $dailyAllowance,
            projected: $projected,
            projectedExceededAt: $projectedExceededAt,
        );
    }
}

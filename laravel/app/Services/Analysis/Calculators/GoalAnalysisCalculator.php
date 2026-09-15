<?php

namespace App\Services\Analysis\Calculators;

use App\Models\AnalysisCard;
use App\Models\Enums\AnalysisGoalSortEnum;
use App\Models\Financial\Goal;
use App\Services\Analysis\DTO\AnalysisCardData;
use App\Services\Analysis\DTO\AnalysisDetailData;
use App\Services\Goal\GoalCalculation;
use App\Services\Goal\GoalCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Modul "Sparziele": sortierte Fortschritts-Balken + Summe.
 *
 * Delegiert die Einzel-Berechnung an den bestehenden GoalCalculationService.
 */
final class GoalAnalysisCalculator extends AbstractAnalysisCalculator
{
    private const int MAX_GOALS_ON_CARD = 12;

    public function card(AnalysisCard $card): AnalysisCardData
    {
        $config = $this->config($card);
        $userId = (int) $card->{AnalysisCard::user_id};
        $currency = $config->currency;
        $sort = $config->goalSort ?? AnalysisGoalSortEnum::NAME_ASC;
        $calculations = $this->goalCalculations($userId, $config->goalIds, $sort);

        $list = [];
        $totalCurrent = 0.0;
        $totalTarget = 0.0;

        foreach ($calculations as $calc) {
            $list[] = [
                'name' => (string) $calc->goal->{Goal::name},
                'current' => $this->formatMoney($calc->currentAmount, $currency),
                'target' => $this->formatMoney($calc->targetAmount, $currency),
                'percentage' => $calc->percentage,
                'progress' => $calc->progressPercentage(),
                'remaining' => $this->formatMoney($calc->remaining, $currency),
                'status_color' => $calc->statusColor(),
            ];

            $totalCurrent += $calc->currentAmount;
            $totalTarget += $calc->targetAmount;
        }

        return new AnalysisCardData(
            title: (string) $card->{AnalysisCard::title},
            module: $card->{AnalysisCard::module},
            totalLabel: 'Stand gesamt',
            totalFormatted: $this->formatMoney($totalCurrent, $currency).' / '.$this->formatMoney($totalTarget, $currency),
            chartType: null,
            list: $list,
        );
    }

    public function detail(AnalysisCard $card): AnalysisDetailData
    {
        $config = $this->config($card);
        $userId = (int) $card->{AnalysisCard::user_id};
        $currency = $config->currency;
        $sort = $config->goalSort ?? AnalysisGoalSortEnum::NAME_ASC;
        $calculations = $this->goalCalculations($userId, $config->goalIds, $sort);

        $labels = [];
        $currentData = [];
        $targetData = [];
        $rows = [];
        $totalCurrent = 0.0;
        $totalTarget = 0.0;

        foreach ($calculations as $calc) {
            $labels[] = (string) $calc->goal->{Goal::name};
            $currentData[] = round($calc->currentAmount, 2);
            $targetData[] = round($calc->targetAmount, 2);
            $totalCurrent += $calc->currentAmount;
            $totalTarget += $calc->targetAmount;

            $rows[] = [
                'name' => (string) $calc->goal->{Goal::name},
                'type' => $calc->goal->type->label(),
                'current' => $this->formatMoney($calc->currentAmount, $currency),
                'target' => $this->formatMoney($calc->targetAmount, $currency),
                'remaining' => $this->formatMoney($calc->remaining, $currency),
                'percent' => $this->formatPercent($calc->percentage),
                'monthly_average' => $this->formatMoney($calc->monthlyAverage, $currency),
            ];
        }

        return new AnalysisDetailData(
            title: (string) $card->{AnalysisCard::title},
            chart: $labels === [] ? null : [
                'type' => 'bar',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Aktuell',
                        'data' => $currentData,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                        'borderColor' => 'rgba(59, 130, 246, 1)',
                        'borderWidth' => 2,
                    ],
                    [
                        'label' => 'Ziel',
                        'data' => $targetData,
                        'backgroundColor' => 'rgba(156, 163, 175, 0.3)',
                        'borderColor' => 'rgba(107, 114, 128, 0.9)',
                        'borderWidth' => 2,
                    ],
                ],
            ],
            stats: [
                ['label' => 'Stand gesamt', 'value' => $this->formatMoney($totalCurrent, $currency), 'color' => $totalCurrent >= $totalTarget ? 'success' : 'warning'],
                ['label' => 'Ziel gesamt', 'value' => $this->formatMoney($totalTarget, $currency)],
            ],
            rows: $rows,
            columns: [
                ['key' => 'name', 'label' => 'Ziel'],
                ['key' => 'type', 'label' => 'Typ'],
                ['key' => 'current', 'label' => 'Aktuell', 'align' => 'right'],
                ['key' => 'target', 'label' => 'Ziel', 'align' => 'right'],
                ['key' => 'remaining', 'label' => 'Offen', 'align' => 'right'],
                ['key' => 'percent', 'label' => '%', 'align' => 'right'],
            ],
        );
    }

    /**
     * @param  list<int>  $goalIds
     * @return Collection<int, GoalCalculation>
     */
    private function goalCalculations(int $userId, array $goalIds, AnalysisGoalSortEnum $sort): Collection
    {
        $query = Goal::query()->activeForUser($userId)->orderBy(Goal::id);

        if ($goalIds !== []) {
            $query->whereIn(Goal::id, $goalIds);
        }

        $goals = $query->limit(self::MAX_GOALS_ON_CARD)->with(Goal::belongs_to_many_transaction_categories)->get();

        $service = app(GoalCalculationService::class);
        $calculations = $goals->map(fn (Goal $goal): GoalCalculation => $service->calculate($goal, CarbonImmutable::now()));

        $sorted = $calculations->all();

        usort($sorted, function (GoalCalculation $a, GoalCalculation $b) use ($sort): int {
            return match ($sort) {
                AnalysisGoalSortEnum::NAME_ASC => strcmp($a->goal->{Goal::name}, $b->goal->{Goal::name}),
                AnalysisGoalSortEnum::NAME_DESC => strcmp($b->goal->{Goal::name}, $a->goal->{Goal::name}),
                AnalysisGoalSortEnum::DATE_ASC => $a->goal->{Goal::created_at}->timestamp <=> $b->goal->{Goal::created_at}->timestamp,
                AnalysisGoalSortEnum::DATE_DESC => $b->goal->{Goal::created_at}->timestamp <=> $a->goal->{Goal::created_at}->timestamp,
                AnalysisGoalSortEnum::PERCENT_ASC => $a->percentage <=> $b->percentage,
                AnalysisGoalSortEnum::PERCENT_DESC => $b->percentage <=> $a->percentage,
                AnalysisGoalSortEnum::AMOUNT_ASC => $a->currentAmount <=> $b->currentAmount,
                AnalysisGoalSortEnum::AMOUNT_DESC => $b->currentAmount <=> $a->currentAmount,
            };
        });

        return Collection::make($sorted);
    }
}

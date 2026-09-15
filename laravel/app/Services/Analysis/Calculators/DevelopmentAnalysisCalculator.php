<?php

namespace App\Services\Analysis\Calculators;

use App\Models\AnalysisCard;
use App\Models\Enums\AnalysisTimeframeEnum;
use App\Models\Financial\Transaction;
use App\Services\Analysis\AnalysisConfiguration;
use App\Services\Analysis\AnalysisTimeframeResolver;
use App\Services\Analysis\AnalysisTransactionQuery;
use App\Services\Analysis\DTO\AnalysisCardData;
use App\Services\Analysis\DTO\AnalysisDetailData;
use Carbon\CarbonImmutable;

/**
 * Modul "Entwicklungen": Bar-Chart der Einnahmen/Ausgaben der letzten 5 Perioden
 * + der durchschnittlichen Bilanz dieser Perioden als Summe.
 *
 * Die Perioden-Granularität (Monat/Quartal/Jahr) richtet sich nach dem konfigurierten
 * Zeitraum; "Eigene Auswahl" fällt auf monatliche Perioden zurück.
 */
final class DevelopmentAnalysisCalculator extends AbstractAnalysisCalculator
{
    private const int PERIODS = 5;

    public function card(AnalysisCard $card): AnalysisCardData
    {
        $config = $this->config($card);
        $periods = $this->buildPeriods($card, $config);

        $incomeData = array_column($periods, 'income');
        $expenseData = array_column($periods, 'expense');
        $labels = array_column($periods, 'label');
        $average = $this->average(array_column($periods, 'net'));

        return new AnalysisCardData(
            title: (string) $card->{AnalysisCard::title},
            module: $card->{AnalysisCard::module},
            totalLabel: 'Ø Bilanz ('.self::PERIODS.' Perioden)',
            totalFormatted: $this->formatMoney($average, $config->currency, true),
            chartType: $labels === [] ? null : 'bar',
            chartLabels: $labels,
            chartDatasets: $labels === [] ? [] : [
                [
                    'label' => 'Einnahmen',
                    'data' => $incomeData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Ausgaben',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2,
                ],
            ],
        );
    }

    public function detail(AnalysisCard $card): AnalysisDetailData
    {
        $config = $this->config($card);
        $periods = $this->buildPeriods($card, $config);

        $rows = [];
        foreach ($periods as $period) {
            $rows[] = [
                'period' => $period['label'],
                'income' => $this->formatMoney($period['income'], $config->currency),
                'expense' => $this->formatMoney($period['expense'], $config->currency),
                'net' => $this->formatMoney($period['net'], $config->currency, true),
            ];
        }

        $average = $this->average(array_column($periods, 'net'));

        return new AnalysisDetailData(
            title: (string) $card->{AnalysisCard::title},
            chart: $periods === [] ? null : [
                'type' => 'bar',
                'labels' => array_column($periods, 'label'),
                'datasets' => [
                    [
                        'label' => 'Einnahmen',
                        'data' => array_column($periods, 'income'),
                        'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                        'borderColor' => 'rgba(16, 185, 129, 1)',
                        'borderWidth' => 2,
                    ],
                    [
                        'label' => 'Ausgaben',
                        'data' => array_column($periods, 'expense'),
                        'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                        'borderColor' => 'rgba(239, 68, 68, 1)',
                        'borderWidth' => 2,
                    ],
                ],
            ],
            stats: [
                ['label' => 'Ø Bilanz ('.self::PERIODS.' Perioden)', 'value' => $this->formatMoney($average, $config->currency, true)],
            ],
            rows: $rows,
            columns: [
                ['key' => 'period', 'label' => 'Periode'],
                ['key' => 'income', 'label' => 'Einnahmen', 'align' => 'right'],
                ['key' => 'expense', 'label' => 'Ausgaben', 'align' => 'right'],
                ['key' => 'net', 'label' => 'Bilanz', 'align' => 'right'],
            ],
        );
    }

    /**
     * @return list<array{label: string, income: float, expense: float, net: float}>
     */
    private function buildPeriods(AnalysisCard $card, AnalysisConfiguration $config): array
    {
        $timeframe = $config->timeframe;
        $effectiveTimeframe = ($timeframe === null || $timeframe->isCustom())
            ? AnalysisTimeframeEnum::MONTHLY
            : $timeframe;

        $bucketConfig = new AnalysisConfiguration(
            module: $config->module,
            timeframe: $effectiveTimeframe,
            accountIds: $config->accountIds,
            categoryIds: $config->categoryIds,
            currency: $config->currency,
        );

        $userId = (int) $card->{AnalysisCard::user_id};
        $resolver = app(AnalysisTimeframeResolver::class);
        $now = CarbonImmutable::now();

        $periods = [];

        for ($offset = self::PERIODS - 1; $offset >= 0; $offset--) {
            $reference = $this->shiftReference($now, $effectiveTimeframe, $offset);
            $range = $resolver->resolve($userId, $bucketConfig, $reference);

            $income = (float) AnalysisTransactionQuery::forCard($card, $bucketConfig, $range['start'], $range['end'])
                ->where(Transaction::amount, '>=', 0)
                ->sum(Transaction::amount);
            $expense = abs((float) AnalysisTransactionQuery::forCard($card, $bucketConfig, $range['start'], $range['end'])
                ->where(Transaction::amount, '<', 0)
                ->sum(Transaction::amount));

            $periods[] = [
                'label' => $this->periodLabel($effectiveTimeframe, $range['start']),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        }

        return $periods;
    }

    private function shiftReference(CarbonImmutable $now, AnalysisTimeframeEnum $timeframe, int $offset): CarbonImmutable
    {
        return match ($timeframe) {
            AnalysisTimeframeEnum::QUARTERLY => $now->subMonths($offset * 3),
            AnalysisTimeframeEnum::YEARLY => $now->subYears($offset),
            default => $now->subMonths($offset),
        };
    }

    private function periodLabel(AnalysisTimeframeEnum $timeframe, CarbonImmutable $start): string
    {
        return match ($timeframe) {
            AnalysisTimeframeEnum::QUARTERLY => sprintf('Q%d %d', $start->quarter, $start->year),
            AnalysisTimeframeEnum::YEARLY => (string) $start->year,
            default => $start->translatedFormat('M Y'),
        };
    }

    /**
     * @param  list<float>  $values
     */
    private function average(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }
}

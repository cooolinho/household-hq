<?php

namespace App\Services\Analysis\Calculators;

use App\Models\AnalysisCard;
use App\Models\Financial\Transaction;
use App\Services\Analysis\AnalysisTimeframeResolver;
use App\Services\Analysis\AnalysisTransactionQuery;
use App\Services\Analysis\DTO\AnalysisCardData;
use App\Services\Analysis\DTO\AnalysisDetailData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Gemeinsame Basis für Einnahmen- und Ausgaben-Auswertungen: Pie-Chart nach
 * Kategorie + Gesamtsumme. Unterscheidet sich nur im Vorzeichen der Beträge
 * und den Beschriftungen.
 */
abstract class AbstractCategoryFlowCalculator extends AbstractAnalysisCalculator
{
    private const int TOP_CATEGORIES = 8;

    abstract protected function isIncome(): bool;

    abstract protected function totalLabel(): string;

    abstract protected function othersLabel(): string;

    abstract protected function uncategorizedLabel(): string;

    public function card(AnalysisCard $card): AnalysisCardData
    {
        $config = $this->config($card);
        $transactions = $this->filteredTransactions($card);
        $currency = $config->currency;

        $total = 0.0;
        $categoryTotals = [];

        foreach ($transactions as $transaction) {
            $amount = abs((float) $transaction->{Transaction::amount});
            $total += $amount;

            $categories = $transaction->{Transaction::belongs_to_many_transaction_categories};
            $names = $categories->isEmpty()
                ? [$this->uncategorizedLabel()]
                : $categories->pluck('name')->all();

            foreach ($names as $name) {
                $categoryTotals[$name] = ($categoryTotals[$name] ?? 0.0) + $amount;
            }
        }

        arsort($categoryTotals);
        [$labels, $data] = $this->topCategoriesWithOthers($categoryTotals, self::TOP_CATEGORIES);

        return new AnalysisCardData(
            title: (string) $card->{AnalysisCard::title},
            module: $card->{AnalysisCard::module},
            totalLabel: $this->totalLabel(),
            totalFormatted: $this->formatMoney($total, $currency),
            chartType: $data === [] ? null : 'doughnut',
            chartLabels: $labels,
            chartDatasets: $data === [] ? [] : [[
                'label' => $this->totalLabel(),
                'data' => $data,
                'backgroundColor' => $this->colors(count($data)),
                'borderWidth' => 2,
            ]],
        );
    }

    public function detail(AnalysisCard $card): AnalysisDetailData
    {
        $config = $this->config($card);
        $transactions = $this->filteredTransactions($card);
        $currency = $config->currency;

        $categoryTotals = [];
        $categoryCounts = [];
        $total = 0.0;

        foreach ($transactions as $transaction) {
            $amount = abs((float) $transaction->{Transaction::amount});
            $total += $amount;

            $categories = $transaction->{Transaction::belongs_to_many_transaction_categories};
            $names = $categories->isEmpty()
                ? [$this->uncategorizedLabel()]
                : $categories->pluck('name')->all();

            foreach ($names as $name) {
                $categoryTotals[$name] = ($categoryTotals[$name] ?? 0.0) + $amount;
                $categoryCounts[$name] = ($categoryCounts[$name] ?? 0) + 1;
            }
        }

        arsort($categoryTotals);

        $rows = [];
        foreach ($categoryTotals as $name => $amount) {
            $rows[] = [
                'category' => $name,
                'amount' => $this->formatMoney($amount, $currency),
                'count' => $categoryCounts[$name] ?? 0,
            ];
        }

        $labels = array_keys($categoryTotals);
        $data = array_values($categoryTotals);

        return new AnalysisDetailData(
            title: (string) $card->{AnalysisCard::title},
            chart: $labels === [] ? null : [
                'type' => 'doughnut',
                'labels' => $labels,
                'datasets' => [[
                    'label' => $this->totalLabel(),
                    'data' => $data,
                    'backgroundColor' => $this->colors(count($data)),
                    'borderWidth' => 2,
                ]],
            ],
            stats: [
                ['label' => $this->totalLabel(), 'value' => $this->formatMoney($total, $currency)],
                ['label' => 'Anzahl Buchungen', 'value' => (string) $transactions->count()],
            ],
            rows: $rows,
            columns: [
                ['key' => 'category', 'label' => 'Kategorie'],
                ['key' => 'amount', 'label' => 'Betrag', 'align' => 'right'],
                ['key' => 'count', 'label' => 'Anzahl', 'align' => 'right'],
            ],
        );
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function filteredTransactions(AnalysisCard $card)
    {
        $config = $this->config($card);
        $userId = (int) $card->{AnalysisCard::user_id};
        $range = app(AnalysisTimeframeResolver::class)->resolve($userId, $config);

        $query = AnalysisTransactionQuery::forCard($card, $config, $range['start'], $range['end']);
        $query->where(Transaction::amount, $this->isIncome() ? '>=' : '<', 0);

        return $query->with(Transaction::belongs_to_many_transaction_categories)->get();
    }

    /**
     * @param  array<string, float>  $categoryTotals  sortiert (desc)
     * @return array{0: list<string>, 1: list<float>}
     */
    private function topCategoriesWithOthers(array $categoryTotals, int $limit): array
    {
        if ($categoryTotals === []) {
            return [[], []];
        }

        $top = array_slice($categoryTotals, 0, $limit, true);
        $rest = array_slice($categoryTotals, $limit, null, true);

        $labels = array_keys($top);
        $data = array_values($top);

        if ($rest !== []) {
            $labels[] = $this->othersLabel();
            $data[] = array_sum($rest);
        }

        return [$labels, $data];
    }
}

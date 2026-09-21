<?php

namespace App\Services\Analysis\Calculators;

use App\Models\AnalysisCard;
use App\Models\Contracts\Taggables;
use App\Models\Financial\Transaction;
use App\Models\Tag;
use App\Services\Analysis\AnalysisTimeframeResolver;
use App\Services\Analysis\AnalysisTransactionQuery;
use App\Services\Analysis\DTO\AnalysisCardData;
use App\Services\Analysis\DTO\AnalysisDetailData;
use Illuminate\Support\Facades\DB;

/**
 * Modul "Tags": Top-3-Tags in Listenformat (sortiert nach Summe der Ausgaben);
 * in der Detailansicht alle Tags als Liste + Bar-Chart.
 */
final class TagAnalysisCalculator extends AbstractAnalysisCalculator
{
    private const int TOP_TAGS_ON_CARD = 3;

    public function card(AnalysisCard $card): AnalysisCardData
    {
        $config = $this->config($card);
        $currency = $config->currency;
        $totals = $this->tagTotals($card);
        $totals = array_slice($totals, 0, self::TOP_TAGS_ON_CARD, true);

        $list = [];
        $maxExpense = 0.0;

        foreach ($totals as $tagName => $total) {
            $maxExpense = max($maxExpense, $total['expense']);
            $list[] = [
                'name' => $tagName,
                'income' => $this->formatMoney($total['income'], $currency),
                'expense' => $this->formatMoney($total['expense'], $currency),
                'net' => $this->formatMoney($total['net'], $currency, true),
            ];
        }

        return new AnalysisCardData(
            title: (string) $card->{AnalysisCard::title},
            module: $card->{AnalysisCard::module},
            totalLabel: 'Top Ausgaben-Tags',
            totalFormatted: $this->formatMoney($maxExpense, $currency),
            chartType: null,
            list: $list,
        );
    }

    public function detail(AnalysisCard $card): AnalysisDetailData
    {
        $config = $this->config($card);
        $currency = $config->currency;
        $totals = $this->tagTotals($card);

        $rows = [];
        $labels = [];
        $incomeData = [];
        $expenseData = [];

        foreach ($totals as $tagName => $total) {
            $labels[] = $tagName;
            $incomeData[] = round($total['income'], 2);
            $expenseData[] = round($total['expense'], 2);
            $rows[] = [
                'name' => $tagName,
                'income' => $this->formatMoney($total['income'], $currency),
                'expense' => $this->formatMoney($total['expense'], $currency),
                'net' => $this->formatMoney($total['net'], $currency, true),
            ];
        }

        return new AnalysisDetailData(
            title: (string) $card->{AnalysisCard::title},
            chart: $labels === [] ? null : [
                'type' => 'bar',
                'labels' => $labels,
                'datasets' => [
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
            ],
            stats: [
                ['label' => 'Anzahl Tags', 'value' => (string) count($totals)],
            ],
            rows: $rows,
            columns: [
                ['key' => 'name', 'label' => 'Tag'],
                ['key' => 'income', 'label' => 'Einnahmen', 'align' => 'right'],
                ['key' => 'expense', 'label' => 'Ausgaben', 'align' => 'right'],
                ['key' => 'net', 'label' => 'Netto', 'align' => 'right'],
            ],
        );
    }

    /**
     * Summen je Tag (Einnahmen / Ausgaben / Netto), sortiert nach Höhe aller Ausgaben (desc).
     *
     * @return array<string, array{income: float, expense: float, net: float}>
     */
    private function tagTotals(AnalysisCard $card): array
    {
        $config = $this->config($card);
        $userId = (int) $card->{AnalysisCard::user_id};
        $currency = $config->currency;
        $range = app(AnalysisTimeframeResolver::class)->resolve($userId, $config);

        $query = AnalysisTransactionQuery::forCard($card, $config, $range['start'], $range['end']);

        $rows = $query
            ->select([
                'taggables.tag_id',
                DB::raw('SUM(CASE WHEN financial_transactions.amount > 0 THEN financial_transactions.amount ELSE 0 END) as income'),
                DB::raw('SUM(CASE WHEN financial_transactions.amount < 0 THEN ABS(financial_transactions.amount) ELSE 0 END) as expense'),
            ])
            ->join(
                Taggables::TABLE,
                'taggables.taggable_id',
                '=',
                'financial_transactions.id',
            )
            ->where('taggables.taggable_type', Transaction::class)
            ->groupBy('taggables.tag_id')
            ->orderByDesc('expense')
            ->get();

        $tagIds = [];
        foreach ($rows as $row) {
            $tagIds[] = (int) $row->tag_id;
        }

        $tagNames = [];
        if ($tagIds !== []) {
            foreach (Tag::query()->whereIn(Tag::id, $tagIds)->get() as $tag) {
                $tagNames[(int) $tag->getKey()] = (string) $tag->name;
            }
        }

        $totals = [];
        foreach ($rows as $row) {
            $name = $tagNames[(int) $row->tag_id] ?? ('#'.$row->tag_id);
            $income = (float) $row->income;
            $expense = (float) $row->expense;

            $totals[$name] = [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        }

        return $totals;
    }
}

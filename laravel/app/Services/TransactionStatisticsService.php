<?php

namespace App\Services;

use App\Models\Contracts\FinancialTransactionCategory;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransactionStatisticsService
{
    private const int CACHE_TTL = 86400; // 24h
    private const string ALIAS_PIVOT = 'pivot';

    // Pivot table has no model constant — defined here for consistency

    // ---------------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------------

    /**
     * Spending and income totals per category for the last N months.
     *
     * Returns:
     *   [
     *     'Lebensmittel' => ['income' => 0.0, 'expense' => 123.45, 'net' => -123.45, 'count' => 7],
     *     ...
     *   ]
     */
    public function getCategorySpending(int $userId, string $currency, int $months = 1): array
    {
        $key = $this->cacheKey('category_spending', $userId, $currency, $months);

        return Cache::remember($key, self::CACHE_TTL, fn() => $this->computeCategorySpending($userId, $currency, $months));
    }

    private function cacheKey(string $method, int|string ...$parts): string
    {
        return 'transaction_stats.' . $method . '.' . implode('.', $parts);
    }

    /**
     * @return array<string, array{income: float, expense: float, net: float, count: int}>
     */
    private function computeCategorySpending(int $userId, string $currency, int $months): array
    {
        $from = CarbonImmutable::today()->subMonths($months)->startOfMonth();

        $rows = DB::table(Transaction::TABLE . ' as t')
            ->join(FinancialTransactionCategory::PIVOT_TABLE . ' as ' . self::ALIAS_PIVOT, self::ALIAS_PIVOT . '.' . FinancialTransactionCategory::TRANSACTION_ID, '=', 't.' . Transaction::id)
            ->join(TransactionCategory::TABLE . ' as c', 'c.' . TransactionCategory::id, '=', self::ALIAS_PIVOT . '.' . FinancialTransactionCategory::CATEGORY_ID)
            ->where('t.' . Transaction::user_id, $userId)
            ->where('t.' . Transaction::amount_currency, strtoupper($currency))
            ->where('t.' . Transaction::date, '>=', $from)
            ->select([
                'c.' . TransactionCategory::name . ' as category_name',
                DB::raw('SUM(CASE WHEN t.' . Transaction::amount . ' >= 0 THEN t.' . Transaction::amount . ' ELSE 0 END) as income'),
                DB::raw('SUM(CASE WHEN t.' . Transaction::amount . ' < 0 THEN ABS(t.' . Transaction::amount . ') ELSE 0 END) as expense'),
                DB::raw('COUNT(DISTINCT t.' . Transaction::id . ') as count'),
            ])
            ->groupBy('c.' . TransactionCategory::id, 'c.' . TransactionCategory::name)
            ->orderByDesc(DB::raw('SUM(CASE WHEN t.' . Transaction::amount . ' < 0 THEN ABS(t.' . Transaction::amount . ') ELSE 0 END)'))
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $income = (float)$row->income;
            $expense = (float)$row->expense;
            $result[$row->category_name] = [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'count' => (int)$row->count,
            ];
        }

        return $result;
    }

    /**
     * Monthly totals per category over the last N months.
     *
     * Returns:
     *   [
     *     'Sep 2026' => ['Lebensmittel' => -123.45, 'Miete' => -900.0, ...],
     *     ...
     *   ]
     */
    public function getMonthlySpendingByCategory(int $userId, string $currency, int $months = 6): array
    {
        $key = $this->cacheKey('monthly_by_category', $userId, $currency, $months);

        return Cache::remember($key, self::CACHE_TTL, fn() => $this->computeMonthlySpendingByCategory($userId, $currency, $months));
    }

    /**
     * @return array<string, array<string, float>>
     */
    private function computeMonthlySpendingByCategory(int $userId, string $currency, int $months): array
    {
        $from = CarbonImmutable::today()->subMonths($months - 1)->startOfMonth();

        $rows = DB::table(Transaction::TABLE . ' as t')
            ->join(FinancialTransactionCategory::PIVOT_TABLE . ' as ' . self::ALIAS_PIVOT, self::ALIAS_PIVOT . '.' . FinancialTransactionCategory::TRANSACTION_ID, '=', 't.' . Transaction::id)
            ->join(TransactionCategory::TABLE . ' as c', 'c.' . TransactionCategory::id, '=', self::ALIAS_PIVOT . '.' . FinancialTransactionCategory::CATEGORY_ID)
            ->where('t.' . Transaction::user_id, $userId)
            ->where('t.' . Transaction::amount_currency, strtoupper($currency))
            ->where('t.' . Transaction::date, '>=', $from)
            ->select([
                DB::raw("DATE_FORMAT(t." . Transaction::date . ", '%Y-%m') as month_key"),
                'c.' . TransactionCategory::name . ' as category_name',
                DB::raw('SUM(t.' . Transaction::amount . ') as net'),
            ])
            ->groupBy(DB::raw("DATE_FORMAT(t." . Transaction::date . ", '%Y-%m')"), 'c.' . TransactionCategory::id, 'c.' . TransactionCategory::name)
            ->orderBy(DB::raw("DATE_FORMAT(t." . Transaction::date . ", '%Y-%m')"))
            ->get();

        // Build label map: 'YYYY-MM' → 'Mon YYYY'
        $result = [];
        $cursor = $from;
        for ($i = 0; $i < $months; $i++) {
            $result[$cursor->format('Y-m')] = [];
            $cursor = $cursor->addMonth();
        }

        foreach ($rows as $row) {
            if (isset($result[$row->month_key])) {
                $result[$row->month_key][$row->category_name] = (float)$row->net;
            }
        }

        // Convert keys to readable labels: 'YYYY-MM' → 'Mon YYYY'
        $labeled = [];
        foreach ($result as $key => $cats) {
            $label = CarbonImmutable::createFromFormat('Y-m', $key)->format('M Y');
            $labeled[$label] = $cats;
        }

        return $labeled;
    }

    // ---------------------------------------------------------------------------
    // Computation helpers
    // ---------------------------------------------------------------------------

    /**
     * Top N categories sorted by total expense (or income).
     *
     * @param string $type 'expense' or 'income'
     * @return array<array{name: string, amount: float, count: int}>
     */
    public function getTopCategories(int $userId, string $currency, string $type = 'expense', int $limit = 5): array
    {
        $key = $this->cacheKey('top_categories', $userId, $currency, $type, $limit);

        return Cache::remember($key, self::CACHE_TTL, fn() => $this->computeTopCategories($userId, $currency, $type, $limit));
    }

    /**
     * @return array<array{name: string, amount: float, count: int}>
     */
    private function computeTopCategories(int $userId, string $currency, string $type, int $limit): array
    {
        $amountExpr = $type === 'income'
            ? DB::raw('SUM(CASE WHEN t.' . Transaction::amount . ' >= 0 THEN t.' . Transaction::amount . ' ELSE 0 END) as total')
            : DB::raw('SUM(CASE WHEN t.' . Transaction::amount . ' < 0 THEN ABS(t.' . Transaction::amount . ') ELSE 0 END) as total');

        $rows = DB::table(Transaction::TABLE . ' as t')
            ->join(FinancialTransactionCategory::PIVOT_TABLE . ' as ' . self::ALIAS_PIVOT, self::ALIAS_PIVOT . '.' . FinancialTransactionCategory::TRANSACTION_ID, '=', 't.' . Transaction::id)
            ->join(TransactionCategory::TABLE . ' as c', 'c.' . TransactionCategory::id, '=', self::ALIAS_PIVOT . '.' . FinancialTransactionCategory::CATEGORY_ID)
            ->where('t.' . Transaction::user_id, $userId)
            ->where('t.' . Transaction::amount_currency, strtoupper($currency))
            ->select(['c.' . TransactionCategory::name . ' as category_name', $amountExpr, DB::raw('COUNT(DISTINCT t.' . Transaction::id . ') as count')])
            ->groupBy('c.' . TransactionCategory::id, 'c.' . TransactionCategory::name)
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return $rows->map(fn($row) => [
            'name' => $row->category_name,
            'amount' => (float)$row->total,
            'count' => (int)$row->count,
        ])->all();
    }

    /**
     * Stats about transactions without any category assignment.
     *
     * Returns:
     *   ['count' => int, 'total_expense' => float, 'total_income' => float]
     */
    public function getUncategorizedStats(int $userId, string $currency): array
    {
        $key = $this->cacheKey('uncategorized_stats', $userId, $currency);

        return Cache::remember($key, self::CACHE_TTL, fn() => $this->computeUncategorizedStats($userId, $currency));
    }

    /**
     * @return array{count: int, total_expense: float, total_income: float}
     */
    private function computeUncategorizedStats(int $userId, string $currency): array
    {
        $row = DB::table(Transaction::TABLE . ' as t')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from(FinancialTransactionCategory::PIVOT_TABLE . ' as ' . self::ALIAS_PIVOT)
                    ->whereColumn(self::ALIAS_PIVOT . '.' . FinancialTransactionCategory::TRANSACTION_ID, 't.' . Transaction::id);
            })
            ->where('t.' . Transaction::user_id, $userId)
            ->where('t.' . Transaction::amount_currency, strtoupper($currency))
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN t.' . Transaction::amount . ' < 0 THEN ABS(t.' . Transaction::amount . ') ELSE 0 END) as total_expense')
            ->selectRaw('SUM(CASE WHEN t.' . Transaction::amount . ' >= 0 THEN t.' . Transaction::amount . ' ELSE 0 END) as total_income')
            ->first();

        return [
            'count' => (int)($row->total_count ?? 0),
            'total_expense' => (float)($row->total_expense ?? 0),
            'total_income' => (float)($row->total_income ?? 0),
        ];
    }

    // ---------------------------------------------------------------------------
    // Cache key
    // ---------------------------------------------------------------------------

    /**
     * Rebuild all cached statistics for a user. Called by RefreshTransactionStatisticsJob.
     */
    public function refreshCache(int $userId, string $currency = 'EUR'): void
    {
        $currency = strtoupper($currency);

        // Clear existing keys and recompute
        foreach ([1, 3, 6, 12] as $months) {
            $spendingKey = $this->cacheKey('category_spending', $userId, $currency, $months);
            Cache::put($spendingKey, $this->computeCategorySpending($userId, $currency, $months), self::CACHE_TTL);

            $monthlyKey = $this->cacheKey('monthly_by_category', $userId, $currency, $months);
            Cache::put($monthlyKey, $this->computeMonthlySpendingByCategory($userId, $currency, $months), self::CACHE_TTL);
        }

        foreach (['expense', 'income'] as $type) {
            foreach ([5, 10] as $limit) {
                $topKey = $this->cacheKey('top_categories', $userId, $currency, $type, $limit);
                Cache::put($topKey, $this->computeTopCategories($userId, $currency, $type, $limit), self::CACHE_TTL);
            }
        }

        $uncatKey = $this->cacheKey('uncategorized_stats', $userId, $currency);
        Cache::put($uncatKey, $this->computeUncategorizedStats($userId, $currency), self::CACHE_TTL);
    }
}

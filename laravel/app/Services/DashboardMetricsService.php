<?php

namespace App\Services;

use App\Models\Document;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\Financial\BankAccount;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use App\Models\Financial\Transaction;
use App\Models\ImportedEmail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    /**
     * @return array{currency: string, forecast: array{income: float, expenses: float, balance: float}, actual: array{income: float, expenses: float, balance: float}}
     */
    public function getMonthlyBalanceData(int $userId, string $currency = 'EUR', ?CarbonImmutable $referenceDate = null): array
    {
        $date = $referenceDate ?? CarbonImmutable::today();
        $normalizedCurrency = $this->normalizeCurrency($currency);

        return [
            'currency' => $normalizedCurrency,
            'forecast' => $this->getForecastMonthlySummary($userId),
            'actual' => $this->getActualMonthlySummary($userId, $normalizedCurrency, $date),
        ];
    }

    private function normalizeCurrency(string $currency): string
    {
        $normalized = strtoupper(trim($currency));

        if ($normalized === '') {
            return 'EUR';
        }

        return $normalized;
    }

    /**
     * @return array{income: float, expenses: float, balance: float}
     */
    private function getForecastMonthlySummary(int $userId): array
    {
        $fixedCosts = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->get([FixedCost::amount, FixedCost::interval]);

        $income = 0.0;
        $expenses = 0.0;

        foreach ($fixedCosts as $fixedCost) {
            $amount = (float)$fixedCost->amount;
            $weightedAmount = abs($amount) * $this->monthlyFactor((string)$fixedCost->interval);

            if ($amount > 0) {
                $income += $weightedAmount;
                continue;
            }

            if ($amount < 0) {
                $expenses += $weightedAmount;
            }
        }

        return [
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $income - $expenses,
        ];
    }

    private function monthlyFactor(string $interval): float
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

    /**
     * @return array{income: float, expenses: float, balance: float}
     */
    private function getActualMonthlySummary(int $userId, string $currency, CarbonImmutable $date): array
    {
        $start = $date->startOfMonth();
        $end = $date->endOfMonth();

        $query = $this->applyCurrencyFilter(
            Transaction::query()
                ->where(Transaction::user_id, $userId)
                ->whereBetween(Transaction::date, [$start->toDateString(), $end->toDateString()]),
            $currency,
        );

        $income = (float)(clone $query)
            ->where(Transaction::amount, '>', 0)
            ->sum(Transaction::amount);

        $expenses = abs((float)(clone $query)
            ->where(Transaction::amount, '<', 0)
            ->sum(Transaction::amount));

        return [
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $income - $expenses,
        ];
    }

    private function applyCurrencyFilter(Builder $query, string $currency): Builder
    {
        if ($currency === '' || $currency === 'ALL') {
            return $query;
        }

        return $query->whereRaw('UPPER(' . Transaction::amount_currency . ') = ?', [$currency]);
    }

    /**
     * @return array{labels: array<int, string>, forecastBalances: array<int, float>, actualBalances: array<int, float>, currency: string}
     */
    public function getMonthlyBalanceTrend(int $userId, string $currency = 'EUR', int $months = 6, ?CarbonImmutable $referenceDate = null): array
    {
        $months = max(1, $months);
        $date = $referenceDate ?? CarbonImmutable::today();
        $normalizedCurrency = $this->normalizeCurrency($currency);
        $startMonth = $date->startOfMonth()->subMonths($months - 1);
        $endMonth = $date->endOfMonth();

        $forecastSummary = $this->getForecastMonthlySummary($userId);

        $transactions = $this->applyCurrencyFilter(
            Transaction::query()
                ->where(Transaction::user_id, $userId)
                ->whereBetween(Transaction::date, [$startMonth->toDateString(), $endMonth->toDateString()]),
            $normalizedCurrency,
        )->get([Transaction::date, Transaction::amount]);

        $groupedByMonth = $transactions->groupBy(
            fn(Transaction $transaction): string => $transaction->date->format('Y-m'),
        );

        $labels = [];
        $forecastBalances = [];
        $actualBalances = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $startMonth->addMonths($i);
            $monthKey = $month->format('Y-m');
            $monthTransactions = $groupedByMonth->get($monthKey, collect());

            $income = (float)$monthTransactions
                ->filter(fn(Transaction $transaction): bool => (float)$transaction->amount > 0)
                ->sum(Transaction::amount);

            $expenses = abs((float)$monthTransactions
                ->filter(fn(Transaction $transaction): bool => (float)$transaction->amount < 0)
                ->sum(Transaction::amount));

            $labels[] = $month->format('m.Y');
            $forecastBalances[] = $forecastSummary['balance'];
            $actualBalances[] = $income - $expenses;
        }

        return [
            'labels' => $labels,
            'forecastBalances' => $forecastBalances,
            'actualBalances' => $actualBalances,
            'currency' => $normalizedCurrency,
        ];
    }

    /**
     * @return array<string, int|float|string>
     */
    public function getOverviewData(int $userId, string $currency = 'EUR'): array
    {
        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $normalizedCurrency = $this->normalizeCurrency($currency);

        $monthlyTransactions = $this->applyCurrencyFilter(
            Transaction::query()
                ->where(Transaction::user_id, $userId)
                ->whereBetween(Transaction::date, [$monthStart->toDateString(), $monthEnd->toDateString()]),
            $normalizedCurrency,
        );

        return [
            'currency' => $normalizedCurrency,
            'fixedCostsCount' => FixedCost::query()->where(FixedCost::user_id, $userId)->count(),
            'insurancesCount' => Insurance::query()->where(Insurance::user_id, $userId)->count(),
            'bankAccountsCount' => BankAccount::query()->where(BankAccount::user_id, $userId)->count(),
            'documentsCount' => Document::query()->where(Document::user_id, $userId)->count(),
            'importedEmailsCount' => ImportedEmail::query()->where(ImportedEmail::user_id, $userId)->count(),
            'measurementDevicesCount' => MeasurementDevice::query()->where(MeasurementDevice::user_id, $userId)->count(),
            'transactionsThisMonthCount' => (clone $monthlyTransactions)->count(),
            'unmatchedTransactionsThisMonthCount' => (clone $monthlyTransactions)->whereNull(Transaction::fixed_cost_id)->count(),
            'upcomingExpensesCount' => FixedCost::query()
                ->where(FixedCost::user_id, $userId)
                ->where(FixedCost::amount, '<', 0)
                ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $today->addDays(30)->toDateString()])
                ->count(),
            'upcomingExpensesTotal' => abs((float)FixedCost::query()
                ->where(FixedCost::user_id, $userId)
                ->where(FixedCost::amount, '<', 0)
                ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $today->addDays(30)->toDateString()])
                ->sum(FixedCost::amount)),
            'expiringInsurancesCount' => Insurance::query()
                ->where(Insurance::user_id, $userId)
                ->whereBetween(Insurance::end_date, [$today->toDateString(), $today->addDays(60)->toDateString()])
                ->count(),
            'nonPreferredCurrencyTransactionsCount' => Transaction::query()
                ->where(Transaction::user_id, $userId)
                ->whereBetween(Transaction::date, [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereNotNull(Transaction::amount_currency)
                ->whereRaw('UPPER(' . Transaction::amount_currency . ') <> ?', [$normalizedCurrency])
                ->count(),
        ];
    }

    /**
     * @return Collection<int, FixedCost>
     */
    public function getUpcomingFixedCosts(int $userId, int $days = 30): Collection
    {
        $today = CarbonImmutable::today();

        return FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->whereBetween(FixedCost::next_booking_date, [$today->toDateString(), $today->addDays($days)->toDateString()])
            ->orderBy(FixedCost::next_booking_date)
            ->orderBy(FixedCost::amount)
            ->get();
    }
}


<?php

namespace App\Services\Budget;

use App\Models\Enums\BudgetStatusEnum;
use App\Models\Financial\Budget;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Zentrale Stelle für Budget-Kalkulationen und Prognosen.
 *
 * Bewusst ohne Cache: Budgets müssen unmittelbar nach einem CSV-Import bzw. einer
 * Neukategorisierung stimmen, und pro Benutzer sind es nur wenige Datensätze.
 */
final class BudgetCalculationService
{
    /**
     * @return list<BudgetCalculation>
     */
    public function calculateForUser(int $userId, ?CarbonImmutable $reference = null): array
    {
        $budgets = Budget::query()
            ->activeForUser($userId)
            ->with(Budget::belongs_to_many_transaction_categories)
            ->get();

        return $budgets
            ->map(fn(Budget $budget): BudgetCalculation => $this->calculate($budget, $reference))
            ->all();
    }

    public function calculate(Budget $budget, ?CarbonImmutable $reference = null): BudgetCalculation
    {
        $reference ??= CarbonImmutable::now();
        ['start' => $start, 'end' => $end] = $this->periodRange($budget, $reference);

        $limit = (float)$budget->amount;
        $spent = $this->spentAmount($budget, $start, $end);
        $remaining = $limit - $spent;
        $percentage = $limit > 0.0 ? $spent / $limit * 100 : 0.0;
        $status = $this->resolveStatus($budget, $percentage);

        $daysTotal = (int)$start->diffInDays($end->startOfDay()) + 1;
        $daysElapsed = min($daysTotal, max(1, (int)$start->diffInDays($reference->startOfDay()) + 1));
        $daysRemaining = max(0, $daysTotal - $daysElapsed);

        $dailyAverage = $spent / $daysElapsed;
        $projected = $dailyAverage * $daysTotal;
        $dailyAllowance = $daysRemaining > 0 ? max(0.0, $remaining) / $daysRemaining : 0.0;

        return new BudgetCalculation(
            budget: $budget,
            periodStart: $start,
            periodEnd: $end,
            limit: $limit,
            spent: $spent,
            remaining: $remaining,
            percentage: $percentage,
            status: $status,
            daysTotal: $daysTotal,
            daysElapsed: $daysElapsed,
            daysRemaining: $daysRemaining,
            dailyAverage: $dailyAverage,
            dailyAllowance: $dailyAllowance,
            projected: $projected,
            projectedExceededAt: $this->projectExceededAt($start, $end, $limit, $spent, $dailyAverage),
        );
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function periodRange(Budget $budget, ?CarbonImmutable $reference = null): array
    {
        $reference ??= CarbonImmutable::now();

        return [
            'start' => $budget->period->periodStart($reference),
            'end' => $budget->period->periodEnd($reference),
        ];
    }

    /**
     * Verbrauch im Zeitraum. Ausgaben sind negativ gespeichert, deshalb wird die Summe
     * invertiert; positive Buchungen (Erstattungen) mindern den Verbrauch, Untergrenze 0.
     */
    public function spentAmount(Budget $budget, CarbonImmutable $start, CarbonImmutable $end): float
    {
        $sum = (float)$this->transactionQuery($budget, $start, $end)->sum(Transaction::amount);

        return max(0.0, -1 * $sum);
    }

    /**
     * Query aller Transaktionen, die auf dieses Budget einzahlen.
     *
     * whereHas statt Join, damit eine Transaktion mit mehreren verknüpften Kategorien
     * nur einmal gezählt wird.
     *
     * @return Builder<Transaction>
     */
    public function transactionQuery(
        Budget           $budget,
        ?CarbonImmutable $start = null,
        ?CarbonImmutable $end = null,
    ): Builder
    {
        $categoryIds = $budget->resolveCategoryIds();

        $query = Transaction::query()
            ->where(Transaction::user_id, $budget->user_id)
            ->where(static fn(Builder|QueryBuilderContract $q) => $q
                ->whereNull(Transaction::amount_currency)
                ->orWhereRaw('UPPER(' . Transaction::amount_currency . ') = ?', [
                    strtoupper($budget->currency),
                ]));

        if ($categoryIds === []) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereHas(
            Transaction::belongs_to_many_transaction_categories,
            static fn(Builder $q): Builder => $q->whereIn(
                TransactionCategory::TABLE . '.' . TransactionCategory::id,
                $categoryIds,
            ),
        );

        if ($start !== null && $end !== null) {
            $query->whereBetween(Transaction::date, [$start, $end]);
        }

        return $query;
    }

    public function resolveStatus(Budget $budget, float $percentage): BudgetStatusEnum
    {
        if ($percentage >= $budget->critical_threshold) {
            return BudgetStatusEnum::EXCEEDED;
        }

        if ($percentage >= $budget->warning_threshold) {
            return BudgetStatusEnum::WARNING;
        }

        return BudgetStatusEnum::OK;
    }

    private function projectExceededAt(
        CarbonImmutable $start,
        CarbonImmutable $end,
        float           $limit,
        float           $spent,
        float           $dailyAverage,
    ): ?CarbonImmutable
    {
        if ($limit <= 0.0 || $spent >= $limit || $dailyAverage <= 0.0) {
            return null;
        }

        $exceededAt = $start->addDays((int)ceil($limit / $dailyAverage) - 1)->startOfDay();

        return $exceededAt->greaterThan($end) ? null : $exceededAt;
    }

    /**
     * Verbrauch der letzten abgeschlossenen Perioden inklusive der laufenden – Basis für Trendanzeigen.
     *
     * @return list<array{label: string, start: CarbonImmutable, end: CarbonImmutable, spent: float, limit: float}>
     */
    public function history(Budget $budget, int $periods = 6, ?CarbonImmutable $reference = null): array
    {
        $reference ??= CarbonImmutable::now();
        $history = [];

        for ($offset = $periods - 1; $offset >= 0; $offset--) {
            $periodReference = $budget->period->shiftStart($reference, -$offset);
            ['start' => $start, 'end' => $end] = $this->periodRange($budget, $periodReference);

            $history[] = [
                'label' => $budget->period->formatRange($start),
                'start' => $start,
                'end' => $end,
                'spent' => $this->spentAmount($budget, $start, $end),
                'limit' => (float)$budget->amount,
            ];
        }

        return $history;
    }
}

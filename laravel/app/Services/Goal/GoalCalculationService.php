<?php

namespace App\Services\Goal;

use App\Models\Enums\GoalTypeEnum;
use App\Models\Financial\Goal;
use App\Models\Financial\GoalContribution;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Zentrale Stelle für Ziel-Kalkulationen und Prognosen.
 *
 * Bewusst ohne Cache, analog zu BudgetCalculationService: Ziele müssen unmittelbar
 * nach einem CSV-Import bzw. einer manuellen Einzahlung stimmen, und pro Benutzer
 * sind es nur wenige Datensätze.
 *
 * Anders als beim Budget gibt es keine Periode – Ziele sind kumulativ ab start_date.
 */
final class GoalCalculationService
{
    /**
     * @return list<GoalCalculation>
     */
    public function calculateForUser(int $userId, ?CarbonImmutable $reference = null): array
    {
        $goals = Goal::query()
            ->activeForUser($userId)
            ->with(Goal::belongs_to_many_transaction_categories)
            ->get();

        return $goals
            ->map(fn(Goal $goal): GoalCalculation => $this->calculate($goal, $reference))
            ->all();
    }

    public function calculate(Goal $goal, ?CarbonImmutable $reference = null): GoalCalculation
    {
        $reference ??= CarbonImmutable::now();
        $startDate = CarbonImmutable::parse($goal->start_date)->startOfDay();
        $targetDate = $goal->target_date !== null
            ? CarbonImmutable::parse($goal->target_date)->startOfDay()
            : null;

        $startAmount = (float)$goal->start_amount;
        $targetAmount = (float)$goal->target_amount;
        $span = $goal->span();

        $transactionContributed = $this->transactionContribution($goal, $reference);
        $manualContributed = $this->manualContribution($goal, $reference);
        $contributed = $transactionContributed + $manualContributed;

        $currentAmount = $goal->type->currentAmount($startAmount, $contributed);
        $remaining = max(0.0, $span - $contributed);
        $percentage = $span > 0.0
            ? ($contributed / $span * 100)
            : ($contributed > 0.0 ? 100.0 : 0.0);

        $monthsElapsed = max(1, (int)$startDate->diffInMonths($reference) + 1);
        $monthlyAverage = $contributed / $monthsElapsed;

        $monthlyContributions = $this->monthlyContributions($goal, $reference);
        $typicalInstallment = $this->medianOfPositive($monthlyContributions);

        $remainingMonths = $monthlyAverage > 0.0
            ? (int)ceil($remaining / $monthlyAverage)
            : null;

        $remainingInstallments = ($goal->type === GoalTypeEnum::DEBT_PAYOFF
            && $typicalInstallment !== null
            && $typicalInstallment > 0.0)
            ? (int)ceil($remaining / $typicalInstallment)
            : null;

        $projectedCompletionAt = $this->projectCompletionAt($reference, $remaining, $monthlyAverage);

        $requiredMonthlyRate = null;

        if ($targetDate !== null) {
            $monthsUntilTarget = (int)$reference->startOfMonth()
                ->diffInMonths($targetDate->startOfMonth(), false);

            $requiredMonthlyRate = $remaining / max(1, $monthsUntilTarget);
        }

        return new GoalCalculation(
            goal: $goal,
            startDate: $startDate,
            targetDate: $targetDate,
            reference: $reference,
            startAmount: $startAmount,
            targetAmount: $targetAmount,
            span: $span,
            transactionContributed: $transactionContributed,
            manualContributed: $manualContributed,
            contributed: $contributed,
            currentAmount: $currentAmount,
            remaining: $remaining,
            percentage: $percentage,
            monthsElapsed: $monthsElapsed,
            monthlyAverage: $monthlyAverage,
            typicalInstallment: $typicalInstallment,
            remainingMonths: $remainingMonths,
            remainingInstallments: $remainingInstallments,
            projectedCompletionAt: $projectedCompletionAt,
            requiredMonthlyRate: $requiredMonthlyRate,
        );
    }

    public function transactionContribution(Goal $goal, ?CarbonImmutable $until = null): float
    {
        $sum = (float)$this->transactionQuery($goal, $until)->sum(Transaction::amount);

        return $goal->direction->normalize($sum);
    }

    /**
     * Query aller Transaktionen, die auf dieses Ziel einzahlen.
     *
     * whereHas statt Join, damit eine Transaktion mit mehreren verknüpften Kategorien
     * nur einmal gezählt wird. Ein Ziel ohne Kategorien liefert bewusst keine
     * Transaktionen – es wird dann rein über manuelle Einzahlungen geführt.
     *
     * @return Builder<Transaction>
     */
    public function transactionQuery(Goal $goal, ?CarbonImmutable $until = null): Builder
    {
        $categoryIds = $goal->resolveCategoryIds();

        $query = Transaction::query()
            ->where(Transaction::user_id, $goal->user_id)
            ->where(static fn(Builder|QueryBuilderContract $q) => $q
                ->whereNull(Transaction::amount_currency)
                ->orWhereRaw('UPPER(' . Transaction::amount_currency . ') = ?', [
                    strtoupper($goal->currency),
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

        $query->where(Transaction::date, '>=', $goal->start_date->copy()->startOfDay());

        if ($until !== null) {
            $query->where(Transaction::date, '<=', $until->endOfDay());
        }

        return $goal->direction->applyAmountFilter($query);
    }

    public function manualContribution(Goal $goal, ?CarbonImmutable $until = null): float
    {
        $query = $goal->contributions()
            ->where(GoalContribution::date, '>=', $goal->start_date->copy()->startOfDay());

        if ($until !== null) {
            $query->where(GoalContribution::date, '<=', $until->endOfDay());
        }

        return (float)$query->sum(GoalContribution::amount);
    }

    /**
     * Beiträge (Transaktionen + manuelle Einzahlungen) gruppiert nach Monat, Basis
     * für Median-Rate und Chart-Reihen.
     *
     * @return array<string, float> 'Y-m' => Beitrag des Monats
     */
    public function monthlyContributions(Goal $goal, ?CarbonImmutable $reference = null): array
    {
        $reference ??= CarbonImmutable::now();
        $buckets = [];

        $transactions = $this->transactionQuery($goal, $reference)
            ->get([Transaction::date, Transaction::amount]);

        foreach ($transactions as $transaction) {
            $key = CarbonImmutable::parse($transaction->date)->format('Y-m');
            $amount = $goal->direction->normalize((float)$transaction->amount);
            $buckets[$key] = ($buckets[$key] ?? 0.0) + $amount;
        }

        $contributions = $goal->contributions()
            ->where(GoalContribution::date, '>=', $goal->start_date->copy()->startOfDay())
            ->where(GoalContribution::date, '<=', $reference->endOfDay())
            ->get([GoalContribution::date, GoalContribution::amount]);

        foreach ($contributions as $contribution) {
            $key = CarbonImmutable::parse($contribution->date)->format('Y-m');
            $buckets[$key] = ($buckets[$key] ?? 0.0) + (float)$contribution->amount;
        }

        ksort($buckets);

        return $buckets;
    }

    /**
     * @param array<string, float> $monthlyContributions
     */
    private function medianOfPositive(array $monthlyContributions): ?float
    {
        $values = array_values(array_filter(
            $monthlyContributions,
            static fn(float $value): bool => $value > 0.0,
        ));

        $count = count($values);

        if ($count < 2) {
            return null;
        }

        sort($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    private function projectCompletionAt(
        CarbonImmutable $reference,
        float           $remaining,
        float           $monthlyAverage,
    ): ?CarbonImmutable
    {
        if ($remaining <= 0.0) {
            return $reference;
        }

        if ($monthlyAverage <= 0.0) {
            return null;
        }

        $monthsNeeded = (int)ceil($remaining / $monthlyAverage);

        return $reference->addMonths($monthsNeeded)->endOfMonth();
    }

    public function typicalInstallment(Goal $goal, ?CarbonImmutable $reference = null): ?float
    {
        return $this->medianOfPositive($this->monthlyContributions($goal, $reference));
    }

    /**
     * Monatsbuckets für das Chart-Widget.
     *
     * @return array{labels: list<string>, monthly: list<float>, cumulative: list<float>,
     *               target: float, plan: list<float>|null}
     */
    public function progressSeries(Goal $goal, ?CarbonImmutable $reference = null, int $maxMonths = 36): array
    {
        $reference ??= CarbonImmutable::now();
        $startMonth = CarbonImmutable::parse($goal->start_date)->startOfMonth();
        $targetMonth = $goal->target_date !== null
            ? CarbonImmutable::parse($goal->target_date)->startOfMonth()
            : null;

        $endMonth = ($targetMonth !== null && $targetMonth->greaterThan($reference))
            ? $targetMonth
            : $reference->startOfMonth();

        $totalMonths = min($maxMonths, max(1, (int)$startMonth->diffInMonths($endMonth) + 1));

        $monthlyContributions = $this->monthlyContributions($goal, $reference);
        $span = $goal->span();
        $planMonths = $targetMonth !== null ? max(1, (int)$startMonth->diffInMonths($targetMonth)) : null;

        $labels = [];
        $monthly = [];
        $cumulative = [];
        $plan = $planMonths !== null ? [] : null;
        $runningTotal = 0.0;

        for ($i = 0; $i < $totalMonths; $i++) {
            $month = $startMonth->addMonths($i);
            $value = $monthlyContributions[$month->format('Y-m')] ?? 0.0;
            $runningTotal += $value;

            $labels[] = $month->translatedFormat('M Y');
            $monthly[] = $value;
            $cumulative[] = $runningTotal;

            if ($plan !== null && $planMonths !== null) {
                $plan[] = min($span, $span / $planMonths * ($i + 1));
            }
        }

        return [
            'labels' => $labels,
            'monthly' => $monthly,
            'cumulative' => $cumulative,
            'target' => $span,
            'plan' => $plan,
        ];
    }
}

<?php

namespace App\Services\FixedCost;

use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\FixedCost;
use App\Services\FixedCostNextBookingDateCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Zentrale Stelle für Fixkosten-Bilanzen: normalisierte Monats-/Jahresbilanz,
 * Zeitraum-Auswertungen auf Basis tatsächlich projizierter Buchungstermine, sowie
 * die Zusammenführung mit den für die Bilanz markierten Budgets.
 */
final class FixedCostBalanceService
{
    private const int MAX_PROJECTION_ITERATIONS = 5000;

    public function __construct(
        private readonly FixedCostAmountNormalizer         $normalizer,
        private readonly FixedCostNextBookingDateCalculator $bookingDates,
    ) {
    }

    /**
     * Normalisierte Monats-/Jahresbilanz (Faktormodell) – für die Bilanz-Widgets.
     */
    public function balanceForUser(
        int    $userId,
        bool   $includeBudgets = true,
        string $currency = Budget::DEFAULT_CURRENCY,
    ): FixedCostBalance {
        $fixedCosts = $this->activeFixedCostsForUser($userId);

        $incomes = $fixedCosts->filter(fn(FixedCost $fc): bool => (float)$fc->amount > 0);
        $expenses = $fixedCosts->filter(fn(FixedCost $fc): bool => (float)$fc->amount < 0);

        $monthlyIncome = (float)$incomes->sum(fn(FixedCost $fc): float => $this->normalizer->monthlyAmount($fc));
        $monthlyFixedCostExpenses = (float)$expenses->sum(fn(FixedCost $fc): float => $this->normalizer->monthlyAmount($fc));
        $yearlyIncome = (float)$incomes->sum(fn(FixedCost $fc): float => $this->normalizer->yearlyAmount($fc));
        $yearlyFixedCostExpenses = (float)$expenses->sum(fn(FixedCost $fc): float => $this->normalizer->yearlyAmount($fc));

        $budgets = $this->balanceRelevantBudgets($userId, $currency);

        return new FixedCostBalance(
            monthlyIncome: $monthlyIncome,
            monthlyFixedCostExpenses: $monthlyFixedCostExpenses,
            monthlyBudgetExpenses: $this->sumBudgetsMonthly($budgets),
            yearlyIncome: $yearlyIncome,
            yearlyFixedCostExpenses: $yearlyFixedCostExpenses,
            yearlyBudgetExpenses: $this->sumBudgetsYearly($budgets),
            budgetsIncluded: $includeBudgets,
            budgetCount: $budgets->count(),
            currency: strtoupper($currency),
            invalidFixedCosts: $this->invalidAmong($fixedCosts),
        );
    }

    /**
     * Summe der projizierten Buchungen im Zeitraum + normalisierte Budgets, über die Monate im Zeitraum verteilt.
     */
    public function periodSummary(
        int             $userId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        bool            $includeBudgets = true,
        string          $currency = Budget::DEFAULT_CURRENCY,
    ): FixedCostPeriodSummary {
        $bookings = $this->projectedBookings($userId, $start, $end);
        $months = max(1, (int)$start->startOfMonth()->diffInMonths($end->startOfMonth()) + 1);

        $income = array_sum(array_map(
            fn(ProjectedBooking $b): float => $b->isIncome() ? $b->amount : 0.0,
            $bookings,
        ));
        $fixedCostExpenses = abs(array_sum(array_map(
            fn(ProjectedBooking $b): float => $b->isIncome() ? 0.0 : $b->amount,
            $bookings,
        )));

        return new FixedCostPeriodSummary(
            start: $start,
            end: $end,
            months: $months,
            income: $income,
            fixedCostExpenses: $fixedCostExpenses,
            budgetExpenses: $this->budgetMonthlyTotal($userId, $currency) * $months,
            budgetsIncluded: $includeBudgets,
            currency: strtoupper($currency),
        );
    }

    /**
     * @return list<FixedCostPeriodBucket>
     */
    public function trend(int $userId, CarbonImmutable $start, CarbonImmutable $end, string $currency = Budget::DEFAULT_CURRENCY): array
    {
        $bookings = $this->projectedBookings($userId, $start, $end);
        $budgetMonthly = $this->budgetMonthlyTotal($userId, $currency);

        $grouped = [];
        foreach ($bookings as $booking) {
            $grouped[$booking->date->format('Y-m')][] = $booking;
        }

        $buckets = [];
        $cursor = $start->startOfMonth();
        $lastMonth = $end->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $key = $cursor->format('Y-m');
            /** @var list<ProjectedBooking> $monthBookings */
            $monthBookings = $grouped[$key] ?? [];

            $income = array_sum(array_map(
                fn(ProjectedBooking $b): float => $b->isIncome() ? $b->amount : 0.0,
                $monthBookings,
            ));
            $expenses = abs(array_sum(array_map(
                fn(ProjectedBooking $b): float => $b->isIncome() ? 0.0 : $b->amount,
                $monthBookings,
            )));

            $buckets[] = new FixedCostPeriodBucket(
                start: $cursor,
                end: $cursor->endOfMonth(),
                label: $cursor->format('m.Y'),
                income: $income,
                fixedCostExpenses: $expenses,
                budgetExpenses: $budgetMonthly,
            );

            $cursor = $cursor->addMonth();
        }

        return $buckets;
    }

    /**
     * @return list<FixedCostCategoryShare>
     */
    public function categoryDistribution(
        int             $userId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        bool            $includeBudgets = true,
        string          $currency = Budget::DEFAULT_CURRENCY,
    ): array {
        $bookings = collect($this->projectedBookings($userId, $start, $end))
            ->filter(fn(ProjectedBooking $b): bool => !$b->isIncome());

        $byCategory = $bookings->groupBy(fn(ProjectedBooking $b): int|string => $b->fixedCost->category_id ?? 'uncategorized');

        $shares = [];

        foreach ($byCategory as $categoryKey => $categoryBookings) {
            /** @var Collection<int, ProjectedBooking> $categoryBookings */
            $amount = abs((float)$categoryBookings->sum(fn(ProjectedBooking $b): float => $b->amount));

            if ($amount <= 0.0) {
                continue;
            }

            $categoryId = $categoryKey === 'uncategorized' ? null : (int)$categoryKey;

            $shares[] = new FixedCostCategoryShare(
                categoryId: $categoryId,
                label: $categoryBookings->first()->categoryLabel(),
                amount: $amount,
            );
        }

        if ($includeBudgets) {
            $months = max(1, (int)$start->startOfMonth()->diffInMonths($end->startOfMonth()) + 1);
            $budgetTotal = $this->budgetMonthlyTotal($userId, $currency) * $months;

            if ($budgetTotal > 0.0) {
                $shares[] = new FixedCostCategoryShare(
                    categoryId: null,
                    label: 'Budgets',
                    amount: $budgetTotal,
                    isBudgetAggregate: true,
                );
            }
        }

        return $shares;
    }

    /**
     * Tatsächliche Buchungstermine im Zeitraum, aufsteigend nach Datum.
     *
     * @return list<ProjectedBooking>
     */
    public function projectedBookings(int $userId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $start = $start->startOfDay();
        $end = $end->endOfDay();
        $bookings = [];

        $fixedCosts = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->with(FixedCost::belongs_to_category)
            ->get();

        foreach ($fixedCosts as $fixedCost) {
            try {
                $cursor = $fixedCost->{FixedCost::next_booking_date}->toImmutable()->startOfDay();
                $floor = $fixedCost->{FixedCost::created_at}?->toImmutable()->startOfDay();

                // 1) Rückwärts bis zur ersten Buchung <= Zeitraumbeginn.
                $iterations = 0;
                while ($cursor->greaterThan($start) && $iterations++ < self::MAX_PROJECTION_ITERATIONS) {
                    $previous = $this->bookingDates->subtractInterval($fixedCost, $cursor);

                    // Kein Fortschritt (korruptes Intervall) -> abbrechen statt endlos laufen.
                    if ($previous->greaterThanOrEqualTo($cursor)) {
                        break;
                    }

                    // Keine erfundene Historie vor dem Anlagedatum.
                    if ($floor !== null && $previous->lessThan($floor)) {
                        break;
                    }

                    $cursor = $previous;
                }

                // 2) Vorwärts sammeln.
                $iterations = 0;
                while ($cursor->lessThanOrEqualTo($end) && $iterations++ < self::MAX_PROJECTION_ITERATIONS) {
                    if (
                        $cursor->greaterThanOrEqualTo($start)
                        && ($floor === null || $cursor->greaterThanOrEqualTo($floor))
                        && $this->bookingDates->isBookingDateAllowed($fixedCost, $cursor)
                    ) {
                        $bookings[] = new ProjectedBooking($fixedCost, $cursor, (float)$fixedCost->amount);
                    }

                    $next = $this->bookingDates->addInterval($fixedCost, $cursor);

                    if ($next->lessThanOrEqualTo($cursor)) {
                        break;
                    }

                    $cursor = $next;
                }
            } catch (InvalidArgumentException) {
                // Kaputte Intervallkonfiguration: Datensatz überspringen, Seite bleibt renderbar.
                continue;
            }
        }

        usort(
            $bookings,
            static fn(ProjectedBooking $a, ProjectedBooking $b): int => [$a->date->getTimestamp(), $a->fixedCost->name]
                <=> [$b->date->getTimestamp(), $b->fixedCost->name],
        );

        return array_values($bookings);
    }

    /** Summierte, auf einen Monat normalisierte Budgets. */
    public function budgetMonthlyTotal(int $userId, string $currency = Budget::DEFAULT_CURRENCY): float
    {
        return $this->sumBudgetsMonthly($this->balanceRelevantBudgets($userId, $currency));
    }

    public function budgetYearlyTotal(int $userId, string $currency = Budget::DEFAULT_CURRENCY): float
    {
        return $this->sumBudgetsYearly($this->balanceRelevantBudgets($userId, $currency));
    }

    /**
     * Wie viel Geld ab `asOf` noch auf dem Konto liegen muss, um alle bis `periodEnd`
     * ausstehenden Fixkosten sicher zu begleichen. Buchungen vor `asOf` (also bereits
     * erledigte Fixkosten der laufenden Periode) fließen bewusst nicht mehr ein.
     */
    public function liquidityRequirement(
        int             $userId,
        CarbonImmutable $asOf,
        CarbonImmutable $periodEnd,
        bool            $includeBudgets = true,
        string          $currency = Budget::DEFAULT_CURRENCY,
    ): FixedCostLiquidityRequirement
    {
        $periodStart = $asOf;
        $bookings = $this->projectedBookings($userId, $asOf, $periodEnd);

        $requiredAmount = abs(array_sum(array_map(
            fn(ProjectedBooking $b): float => $b->isIncome() ? 0.0 : $b->amount,
            $bookings,
        )));
        $expectedIncome = array_sum(array_map(
            fn(ProjectedBooking $b): float => $b->isIncome() ? $b->amount : 0.0,
            $bookings,
        ));

        $budgetShare = 0.0;
        if ($includeBudgets) {
            $totalDays = max(1, $asOf->startOfDay()->diffInDays($periodEnd->startOfDay()) + 1);
            $daysInMonth = max(1, $asOf->daysInMonth);
            $budgetShare = $this->budgetMonthlyTotal($userId, $currency) * ($totalDays / $daysInMonth);
        }

        $bankAccounts = BankAccount::query()
            ->where(BankAccount::user_id, $userId)
            ->get([BankAccount::balance, BankAccount::balance_date]);

        $bankBalance = (float)$bankAccounts->sum(fn(BankAccount $account): float => (float)$account->balance);
        $bankBalanceAsOf = $bankAccounts
            ->pluck(BankAccount::balance_date)
            ->filter()
            ->map(fn($date): CarbonImmutable => CarbonImmutable::parse($date))
            ->sort()
            ->first();

        return new FixedCostLiquidityRequirement(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            asOf: $asOf,
            requiredAmount: $requiredAmount,
            expectedIncome: $expectedIncome,
            budgetShare: $budgetShare,
            bankBalance: $bankBalance,
            bankBalanceAsOf: $bankBalanceAsOf,
            bookings: $bookings,
            budgetsIncluded: $includeBudgets,
            currency: strtoupper($currency),
        );
    }

    /**
     * @return list<FixedCost>
     */
    public function invalidFixedCosts(int $userId): array
    {
        return $this->invalidAmong($this->activeFixedCostsForUser($userId));
    }

    /**
     * @return Collection<int, FixedCost>
     */
    private function activeFixedCostsForUser(int $userId): Collection
    {
        return FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->get();
    }

    /**
     * @param Collection<int, FixedCost> $fixedCosts
     * @return list<FixedCost>
     */
    private function invalidAmong(Collection $fixedCosts): array
    {
        return $fixedCosts
            ->filter(fn(FixedCost $fc): bool => !$this->normalizer->isConfigurationValid($fc))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Budget>
     */
    private function balanceRelevantBudgets(int $userId, string $currency): Collection
    {
        return Budget::query()
            ->includedInBalanceForUser($userId)
            ->whereRaw('UPPER(' . Budget::currency . ') = ?', [strtoupper($currency)])
            ->get([Budget::id, Budget::amount, Budget::period, Budget::currency]);
    }

    /**
     * @param Collection<int, Budget> $budgets
     */
    private function sumBudgetsMonthly(Collection $budgets): float
    {
        return (float)$budgets->sum(fn(Budget $budget): float => (float)$budget->amount * $budget->period->monthlyFactor());
    }

    /**
     * @param Collection<int, Budget> $budgets
     */
    private function sumBudgetsYearly(Collection $budgets): float
    {
        return (float)$budgets->sum(fn(Budget $budget): float => (float)$budget->amount * $budget->period->occurrencesPerYear());
    }
}

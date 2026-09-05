<?php

namespace App\Services;

use App\Models\Contracts\FinancialTransactionCategory;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionCategorizationService
{
    /**
     * Kategorisiert alle nicht kategorisierten Transaktionen eines Users.
     *
     * @return array{processed: int, categorized: int, skipped: int}
     */
    public function categorizeUncategorized(int $userId): array
    {
        $categories = $this->getCategoriesForUser($userId);

        if ($categories->isEmpty()) {
            return $this->emptyResults();
        }

        $transactions = Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->whereDoesntHave(Transaction::belongs_to_many_transaction_categories)
            ->get();

        return $this->categorizeTransactions($transactions, $categories);
    }

    /**
     * Ergänzt passende Kategorien bei bereits kategorisierten Transaktionen.
     *
     * @return array{processed: int, categorized: int, skipped: int}
     */
    public function recategorizeCategorized(int $userId): array
    {
        $categories = $this->getCategoriesForUser($userId);

        if ($categories->isEmpty()) {
            return $this->emptyResults();
        }

        $transactions = Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->whereHas(Transaction::belongs_to_many_transaction_categories)
            ->get();

        return $this->categorizeTransactions($transactions, $categories);
    }

    /**
     * Entfernt alle Zuordnungen und kategorisiert sämtliche Transaktionen neu.
     *
     * @return array{processed: int, categorized: int, skipped: int}
     */
    public function recategorizeAll(int $userId): array
    {
        $categories = $this->getCategoriesForUser($userId);
        $transactions = Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->get();

        $transactionIds = $transactions
            ->pluck(Transaction::id)
            ->all();

        return DB::transaction(function () use ($transactionIds, $transactions, $categories): array {
            if ($transactionIds !== []) {
                DB::table(FinancialTransactionCategory::PIVOT_TABLE)
                    ->whereIn(FinancialTransactionCategory::TRANSACTION_ID, $transactionIds)
                    ->delete();
            }

            return $this->categorizeTransactions($transactions, $categories);
        });
    }

    /**
     * @return Collection<int, TransactionCategory>
     */
    private function getCategoriesForUser(int $userId): Collection
    {
        return TransactionCategory::query()
            ->visibleForUser($userId)
            ->where(TransactionCategory::active, true)
            ->with([
                TransactionCategory::has_many_rules => function ($query) use ($userId) {
                    $query
                        ->where(function ($query) use ($userId) {
                            $query->whereNull(TransactionCategoryRule::user_id)
                                ->orWhere(TransactionCategoryRule::user_id, $userId);
                        })
                        ->with([
                            TransactionCategoryRule::has_many_criteria,
                            TransactionCategoryRule::has_many_user_settings => function ($query) use ($userId) {
                                $query->where(TransactionCategoryRuleUserSetting::user_id, $userId);
                            },
                        ]);
                },
            ])
            ->get();
    }

    /**
     * @param Collection<int, Transaction> $transactions
     * @param Collection<int, TransactionCategory> $categories
     * @return array{processed: int, categorized: int, skipped: int}
     */
    private function categorizeTransactions(Collection $transactions, Collection $categories): array
    {
        $processed = 0;
        $categorized = 0;
        $skipped = 0;

        foreach ($transactions as $transaction) {
            $matchingCategories = $this->matchTransaction($transaction, $categories);

            $processed++;

            if ($matchingCategories->isEmpty()) {
                $skipped++;

                continue;
            }

            $transaction->transactionCategories()->syncWithoutDetaching(
                $matchingCategories->pluck(TransactionCategory::id)->toArray()
            );
            $categorized++;
        }

        return compact('processed', 'categorized', 'skipped');
    }

    /**
     * @return array{processed: int, categorized: int, skipped: int}
     */
    private function emptyResults(): array
    {
        return ['processed' => 0, 'categorized' => 0, 'skipped' => 0];
    }

    /**
     * Ermittelt alle passenden Kategorien für eine Transaktion.
     *
     * @param Collection<int, TransactionCategory> $categories
     * @return Collection<int, TransactionCategory>
     */
    public function matchTransaction(Transaction $transaction, Collection $categories): Collection
    {
        return $categories->filter(function (TransactionCategory $category) use ($transaction): bool {
            $userId = (int)$transaction->user_id;
            $activeRules = $category->rules->filter(
                fn(TransactionCategoryRule $r) => $this->isRuleActiveForUser($r, $userId)
            );

            if ($activeRules->isEmpty()) {
                return false;
            }

            // Eine Kategorie matched, wenn mindestens eine Regel matched (OR zwischen Regeln)
            foreach ($activeRules as $rule) {
                if ($this->evaluateRule($transaction, $rule)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    private function isRuleActiveForUser(TransactionCategoryRule $rule, int $userId): bool
    {
        // User overrides exist only for globally defined rules.
        if ($rule->user_id === null) {
            /** @var TransactionCategoryRuleUserSetting|null $override */
            $override = $rule->userSettings
                ->firstWhere(TransactionCategoryRuleUserSetting::user_id, $userId);

            if ($override !== null) {
                return $override->active;
            }
        }

        return $rule->active;
    }

    /**
     * Evaluiert eine Regel gegen eine Transaktion.
     * operator=AND: alle Kriterien müssen passen
     * operator=OR: mindestens ein Kriterium muss passen
     */
    public function evaluateRule(Transaction $transaction, TransactionCategoryRule $rule): bool
    {
        $criteria = $rule->criteria;

        if ($criteria->isEmpty()) {
            return false;
        }

        if ($rule->operator === TransactionCategoryRule::OPERATOR_AND) {
            foreach ($criteria as $criterion) {
                if (!$this->evaluateCriterion($transaction, $criterion)) {
                    return false;
                }
            }

            return true;
        }

        // OR
        foreach ($criteria as $criterion) {
            if ($this->evaluateCriterion($transaction, $criterion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluiert ein einzelnes Kriterium gegen eine Transaktion.
     */
    public function evaluateCriterion(Transaction $transaction, TransactionCategoryCriterion $criterion): bool
    {
        $fieldValue = $this->getFieldValue($transaction, $criterion->field);

        if ($fieldValue === null) {
            return false;
        }

        return match ($criterion->operator) {
            TransactionCategoryCriterion::OP_EQUALS => $this->opEquals($fieldValue, $criterion->value, $criterion->case_sensitive),
            TransactionCategoryCriterion::OP_CONTAINS => $this->opContains($fieldValue, $criterion->value, $criterion->case_sensitive),
            TransactionCategoryCriterion::OP_STARTS_WITH => $this->opStartsWith($fieldValue, $criterion->value, $criterion->case_sensitive),
            TransactionCategoryCriterion::OP_ENDS_WITH => $this->opEndsWith($fieldValue, $criterion->value, $criterion->case_sensitive),
            TransactionCategoryCriterion::OP_REGEX => $this->opRegex($fieldValue, $criterion->value),
            TransactionCategoryCriterion::OP_GREATER_THAN => $this->opGreaterThan($fieldValue, $criterion->value),
            TransactionCategoryCriterion::OP_LESS_THAN => $this->opLessThan($fieldValue, $criterion->value),
            TransactionCategoryCriterion::OP_BETWEEN => $this->opBetween($fieldValue, $criterion->value, $criterion->value_secondary),
            default => false,
        };
    }

    private function getFieldValue(Transaction $transaction, string $field): ?string
    {
        return match ($field) {
            TransactionCategoryCriterion::FIELD_DATE => $transaction->date?->format('Y-m-d'),
            TransactionCategoryCriterion::FIELD_PAYER => $transaction->payer,
            TransactionCategoryCriterion::FIELD_DESCRIPTION => $transaction->description,
            TransactionCategoryCriterion::FIELD_PURPOSE => $transaction->purpose,
            TransactionCategoryCriterion::FIELD_AMOUNT => (string)$transaction->amount,
            TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY => $transaction->amount_currency,
            default => null,
        };
    }

    private function opEquals(string $fieldValue, string $criterionValue, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return mb_strtolower($fieldValue) === mb_strtolower($criterionValue);
        }

        return $fieldValue === $criterionValue;
    }

    private function opContains(string $fieldValue, string $criterionValue, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return str_contains(mb_strtolower($fieldValue), mb_strtolower($criterionValue));
        }

        return str_contains($fieldValue, $criterionValue);
    }

    private function opStartsWith(string $fieldValue, string $criterionValue, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return str_starts_with(mb_strtolower($fieldValue), mb_strtolower($criterionValue));
        }

        return str_starts_with($fieldValue, $criterionValue);
    }

    private function opEndsWith(string $fieldValue, string $criterionValue, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return str_ends_with(mb_strtolower($fieldValue), mb_strtolower($criterionValue));
        }

        return str_ends_with($fieldValue, $criterionValue);
    }

    private function opRegex(string $fieldValue, string $pattern): bool
    {
        try {
            return (bool)preg_match($pattern, $fieldValue);
        } catch (\Throwable) {
            return false;
        }
    }

    private function opGreaterThan(string $fieldValue, string $criterionValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($criterionValue)
            && (float)$fieldValue > (float)$criterionValue;
    }

    private function opLessThan(string $fieldValue, string $criterionValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($criterionValue)
            && (float)$fieldValue < (float)$criterionValue;
    }

    private function opBetween(string $fieldValue, string $min, ?string $max): bool
    {
        if ($max === null || !is_numeric($fieldValue) || !is_numeric($min) || !is_numeric($max)) {
            return false;
        }

        $val = (float)$fieldValue;

        return $val >= (float)$min && $val <= (float)$max;
    }
}

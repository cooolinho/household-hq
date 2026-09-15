<?php

namespace App\Services\Analysis;

use App\Models\AnalysisCard;
use App\Models\Contracts\Taggables;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * Zentrale Query-Builder-Hilfen für alle Analysis-Calculator.
 *
 * Liefert einen vor-gefilterten Transaction-Query (User, Konto, Kategorie, Tag, Zeitraum, Währung).
 */
final class AnalysisTransactionQuery
{
    /**
     * @param  list<int>  $accountIds
     * @param  list<int>  $categoryIds
     * @param  list<int>  $tagIds
     * @return Builder<Transaction>
     */
    public static function baseQuery(
        int $userId,
        array $accountIds,
        array $categoryIds,
        array $tagIds,
        ?CarbonImmutable $dateFrom,
        ?CarbonImmutable $dateTo,
        ?string $currency,
    ): Builder {
        $query = Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->where(static fn (Builder|QueryBuilderContract $q) => $q
                ->whereNull(Transaction::amount_currency)
                ->orWhereRaw('UPPER('.Transaction::amount_currency.') = ?', [strtoupper($currency ?? 'EUR')]));

        if ($accountIds !== []) {
            $query->whereIn(Transaction::bank_account_id, $accountIds);
        }

        if ($categoryIds !== []) {
            $query->whereHas(
                Transaction::belongs_to_many_transaction_categories,
                static fn (Builder $q): Builder => $q->whereIn(
                    TransactionCategory::TABLE.'.'.TransactionCategory::id,
                    $categoryIds,
                ),
            );
        }

        if ($tagIds !== []) {
            $query->whereHas(
                Transaction::morph_to_many_tags,
                static fn (Builder $q): Builder => $q->whereIn(
                    Taggables::TABLE.'.'.Taggables::tag_id,
                    $tagIds,
                ),
            );
        }

        if ($dateFrom !== null) {
            $query->whereDate(Transaction::date, '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate(Transaction::date, '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Baut einen gefilterten Query für eine AnalysisCard.
     * Erweitert Kategorie-IDs um Nachkommen, wenn Eltern ausgewählt.
     *
     * @param  list<int>  $categoryIds
     * @return Builder<Transaction>
     */
    public static function forCard(
        AnalysisCard $card,
        AnalysisConfiguration $config,
        ?CarbonImmutable $dateFrom = null,
        ?CarbonImmutable $dateTo = null,
    ): Builder {
        $categoryIds = $config->categoryIds;

        // Nachkommen-Kategorien auflösen (wie Budget/Goal)
        if ($categoryIds !== []) {
            $categoryIds = self::resolveCategoryDescendants($categoryIds);
        }

        $accountIds = $config->accountIds;
        $tagIds = $config->tagIds;
        $currency = $config->currency;

        // Zeitrahmen: bevorzugt explizit übergeben (für Custom/Override), sonst aus TimeframeResolver
        $from = $dateFrom ?? $config->dateFrom;
        $to = $dateTo ?? $config->dateTo;

        return self::baseQuery(
            userId: (int) $card->user_id,
            accountIds: $accountIds,
            categoryIds: $categoryIds,
            tagIds: $tagIds,
            dateFrom: $from,
            dateTo: $to,
            currency: $currency,
        );
    }

    /**
     * Liefert alle Nachkommen-IDs für die übergebenen Kategorie-IDs (inkl. der übergebenen selbst).
     *
     * @param  list<int>  $categoryIds
     * @return list<int>
     */
    private static function resolveCategoryDescendants(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $allIds = $categoryIds;

        foreach (TransactionCategory::query()->whereIn(TransactionCategory::id, $categoryIds)->get() as $category) {
            $allIds = [...$allIds, ...$category->getDescendantIds()];
        }

        return array_values(array_unique($allIds));
    }
}

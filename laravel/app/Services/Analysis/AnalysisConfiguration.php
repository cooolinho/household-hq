<?php

namespace App\Services\Analysis;

use App\Models\Enums\AnalysisBudgetIntervalEnum;
use App\Models\Enums\AnalysisGoalSortEnum;
use App\Models\Enums\AnalysisModuleEnum;
use App\Models\Enums\AnalysisTimeframeEnum;
use Carbon\CarbonImmutable;

/**
 * Typisierte Konfiguration einer Auswertungs-Karte.
 *
 * Entsteht aus dem JSON-Feld `configuration` der AnalysisCard.
 * Leere ID-Listen bedeuten "alle" (Konten / Kategorien / Budgets / Sparziele / Tags).
 */
final readonly class AnalysisConfiguration
{
    /**
     * @param  list<int>  $accountIds
     * @param  list<int>  $categoryIds
     * @param  list<int>  $budgetIds
     * @param  list<int>  $goalIds
     * @param  list<int>  $tagIds
     */
    public function __construct(
        public AnalysisModuleEnum $module,
        public ?AnalysisTimeframeEnum $timeframe = null,
        public ?CarbonImmutable $dateFrom = null,
        public ?CarbonImmutable $dateTo = null,
        public ?AnalysisGoalSortEnum $goalSort = null,
        public ?AnalysisBudgetIntervalEnum $budgetInterval = null,
        public array $accountIds = [],
        public array $categoryIds = [],
        public array $budgetIds = [],
        public array $goalIds = [],
        public array $tagIds = [],
        public string $currency = 'EUR',
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(AnalysisModuleEnum $module, array $raw): self
    {
        $timeframeName = (string) ($raw['timeframe'] ?? '');
        $timeframe = $timeframeName !== ''
            ? (AnalysisTimeframeEnum::tryFrom($timeframeName) ?? AnalysisTimeframeEnum::MONTHLY)
            : null;

        $goalSortName = (string) ($raw['goal_sort'] ?? '');
        $goalSort = $goalSortName !== ''
            ? (AnalysisGoalSortEnum::tryFrom($goalSortName) ?? AnalysisGoalSortEnum::NAME_ASC)
            : null;

        $budgetIntervalName = (string) ($raw['budget_interval'] ?? '');
        $budgetInterval = $budgetIntervalName !== ''
            ? (AnalysisBudgetIntervalEnum::tryFrom($budgetIntervalName) ?? AnalysisBudgetIntervalEnum::MONTHLY)
            : null;

        $dateFrom = filled($raw['date_from'] ?? null)
            ? CarbonImmutable::parse((string) $raw['date_from'])->startOfDay()
            : null;
        $dateTo = filled($raw['date_to'] ?? null)
            ? CarbonImmutable::parse((string) $raw['date_to'])->endOfDay()
            : null;

        return new self(
            module: $module,
            timeframe: $timeframe,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            goalSort: $goalSort,
            budgetInterval: $budgetInterval,
            accountIds: self::intList($raw['account_ids'] ?? []),
            categoryIds: self::intList($raw['category_ids'] ?? []),
            budgetIds: self::intList($raw['budget_ids'] ?? []),
            goalIds: self::intList($raw['goal_ids'] ?? []),
            tagIds: self::intList($raw['tag_ids'] ?? []),
            currency: strtoupper(trim((string) ($raw['currency'] ?? 'EUR'))) ?: 'EUR',
        );
    }

    /**
     * @return list<int>
     */
    private static function intList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $entry) {
            if ($entry === null || $entry === '') {
                continue;
            }

            $ids[] = (int) $entry;
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }
}

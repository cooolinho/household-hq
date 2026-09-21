<?php

namespace App\Services\Analysis;

use App\Models\AnalysisCard;
use App\Models\DashboardWidgetPreference;
use App\Models\Enums\AnalysisBudgetIntervalEnum;
use App\Models\Enums\AnalysisGoalSortEnum;
use App\Models\Enums\AnalysisModuleEnum;
use App\Models\Enums\AnalysisTimeframeEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\Goal;
use App\Models\Financial\TransactionCategory;
use App\Models\Tag;
use App\Services\Analysis\Calculators\BudgetAnalysisCalculator;
use App\Services\Analysis\Calculators\DevelopmentAnalysisCalculator;
use App\Services\Analysis\Calculators\ExpenseAnalysisCalculator;
use App\Services\Analysis\Calculators\GoalAnalysisCalculator;
use App\Services\Analysis\Calculators\IncomeAnalysisCalculator;
use App\Services\Analysis\Calculators\TagAnalysisCalculator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use InvalidArgumentException;

/**
 * Zentrales Verzeichnis der 6 Auswertungs-Module: Konfigurationsformular,
 * Defaults, Normalisierung und Verknüpfung zum jeweiligen Calculator.
 */
final class AnalysisModuleRegistry
{
    public function calculatorFor(AnalysisModuleEnum $module): AnalysisCalculatorContract
    {
        return app(match ($module) {
            AnalysisModuleEnum::BUDGETS => BudgetAnalysisCalculator::class,
            AnalysisModuleEnum::SAVINGS_GOALS => GoalAnalysisCalculator::class,
            AnalysisModuleEnum::INCOME => IncomeAnalysisCalculator::class,
            AnalysisModuleEnum::EXPENSES => ExpenseAnalysisCalculator::class,
            AnalysisModuleEnum::DEVELOPMENTS => DevelopmentAnalysisCalculator::class,
            AnalysisModuleEnum::TAGS => TagAnalysisCalculator::class,
        });
    }

    /**
     * @return array<int, Component>
     */
    public function configSchema(AnalysisModuleEnum $module): array
    {
        return match ($module) {
            AnalysisModuleEnum::BUDGETS => [
                Select::make('budget_interval')
                    ->label('Intervall')
                    ->options(AnalysisBudgetIntervalEnum::options())
                    ->default(AnalysisBudgetIntervalEnum::default())
                    ->required(),
                Select::make('budget_ids')
                    ->label('Budgets')
                    ->options(fn (): array => Budget::query()
                        ->activeForUser((int) auth()->id())
                        ->orderBy(Budget::name)
                        ->pluck(Budget::name, Budget::id)
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->placeholder('Alle Budgets'),
            ],
            AnalysisModuleEnum::SAVINGS_GOALS => [
                Select::make('goal_sort')
                    ->label('Sortierung')
                    ->options(AnalysisGoalSortEnum::options())
                    ->default(AnalysisGoalSortEnum::default())
                    ->required(),
                Select::make('goal_ids')
                    ->label('Sparziele')
                    ->options(fn (): array => Goal::query()
                        ->activeForUser((int) auth()->id())
                        ->orderBy(Goal::name)
                        ->pluck(Goal::name, Goal::id)
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->placeholder('Alle Sparziele'),
            ],
            AnalysisModuleEnum::INCOME,
            AnalysisModuleEnum::EXPENSES,
            AnalysisModuleEnum::DEVELOPMENTS => [
                $this->accountsSelect(),
                $this->categoriesSelect(),
                ...$this->timeframeFields(),
            ],
            AnalysisModuleEnum::TAGS => [
                $this->accountsSelect(),
                $this->tagsSelect(),
                ...$this->timeframeFields(),
            ],
            default => throw new InvalidArgumentException('Unbekanntes Auswertungs-Modul.'),
        };
    }

    /**
     * Standard-Konfiguration für ein frisch angelegtes Modul.
     *
     * @return array<string, mixed>
     */
    public function defaults(AnalysisModuleEnum $module): array
    {
        return match ($module) {
            AnalysisModuleEnum::BUDGETS => [
                'budget_interval' => AnalysisBudgetIntervalEnum::default(),
                'budget_ids' => [],
            ],
            AnalysisModuleEnum::SAVINGS_GOALS => [
                'goal_sort' => AnalysisGoalSortEnum::default(),
                'goal_ids' => [],
            ],
            AnalysisModuleEnum::INCOME,
            AnalysisModuleEnum::EXPENSES,
            AnalysisModuleEnum::DEVELOPMENTS,
            AnalysisModuleEnum::TAGS => [
                'account_ids' => [],
                'category_ids' => [],
                'tag_ids' => [],
                'timeframe' => AnalysisTimeframeEnum::default(),
                'date_from' => null,
                'date_to' => null,
                'currency' => $this->defaultCurrency(),
            ],
            default => throw new InvalidArgumentException('Unbekanntes Auswertungs-Modul.'),
        };
    }

    /**
     * Bereinigt und validiert eine Konfiguration vor dem Speichern.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normalize(AnalysisModuleEnum $module, array $raw): array
    {
        $defaults = $this->defaults($module);
        $raw = array_replace($defaults, array_intersect_key($raw, $defaults));

        return match ($module) {
            AnalysisModuleEnum::BUDGETS => [
                'budget_interval' => AnalysisBudgetIntervalEnum::tryFrom((string) ($raw['budget_interval'] ?? ''))?->name ?? AnalysisBudgetIntervalEnum::default(),
                'budget_ids' => $this->intList($raw['budget_ids'] ?? []),
            ],
            AnalysisModuleEnum::SAVINGS_GOALS => [
                'goal_sort' => AnalysisGoalSortEnum::tryFrom((string) ($raw['goal_sort'] ?? ''))?->name ?? AnalysisGoalSortEnum::default(),
                'goal_ids' => $this->intList($raw['goal_ids'] ?? []),
            ],
            AnalysisModuleEnum::INCOME,
            AnalysisModuleEnum::EXPENSES,
            AnalysisModuleEnum::DEVELOPMENTS,
            AnalysisModuleEnum::TAGS => [
                'account_ids' => $this->intList($raw['account_ids'] ?? []),
                'category_ids' => $this->intList($raw['category_ids'] ?? []),
                'tag_ids' => $this->intList($raw['tag_ids'] ?? []),
                'timeframe' => AnalysisTimeframeEnum::tryFrom((string) ($raw['timeframe'] ?? ''))?->name ?? AnalysisTimeframeEnum::default(),
                'date_from' => filled($raw['date_from'] ?? null) ? (string) $raw['date_from'] : null,
                'date_to' => filled($raw['date_to'] ?? null) ? (string) $raw['date_to'] : null,
                'currency' => $this->normalizeCurrency($raw['currency'] ?? null),
            ],
            default => throw new InvalidArgumentException('Unbekanntes Auswertungs-Modul.'),
        };
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function configurationFor(AnalysisCard $card): AnalysisConfiguration
    {
        $raw = is_array($card->{AnalysisCard::configuration}) ? $card->{AnalysisCard::configuration} : [];

        return AnalysisConfiguration::fromArray($card->{AnalysisCard::module}, $raw);
    }

    private function accountsSelect(): Select
    {
        return Select::make('account_ids')
            ->label('Konten')
            ->options(fn (): array => BankAccount::query()
                ->where(BankAccount::user_id, (int) auth()->id())
                ->orderBy(BankAccount::name)
                ->pluck(BankAccount::name, BankAccount::id)
                ->all())
            ->multiple()
            ->searchable()
            ->preload()
            ->placeholder('Alle Konten');
    }

    private function categoriesSelect(): Select
    {
        return Select::make('category_ids')
            ->label('Kategorien')
            ->options(fn (): array => TransactionCategory::query()
                ->where(TransactionCategory::active, true)
                ->visibleForUser((int) auth()->id())
                ->with(TransactionCategory::belongs_to_parent)
                ->get()
                ->sortBy(fn (TransactionCategory $category): string => $category->getFullNameAttribute())
                ->mapWithKeys(fn (TransactionCategory $category): array => [
                    $category->getKey() => $category->getFullNameAttribute(),
                ])
                ->all())
            ->multiple()
            ->searchable()
            ->preload()
            ->placeholder('Alle Kategorien');
    }

    private function tagsSelect(): Select
    {
        return Select::make('tag_ids')
            ->label('Tags')
            ->options(fn (): array => Tag::query()
                ->where(Tag::user_id, (int) auth()->id())
                ->get()
                ->mapWithKeys(fn (Tag $tag): array => [$tag->getKey() => (string) $tag->name])
                ->all())
            ->multiple()
            ->searchable()
            ->preload()
            ->placeholder('Alle Tags');
    }

    /**
     * Zeitraum-Auswahl + optionale eigene Datumswahl (nur bei CUSTOM sichtbar).
     *
     * @return array<int, Component>
     */
    private function timeframeFields(): array
    {
        return [
            Select::make('timeframe')
                ->label('Zeitraum')
                ->options(AnalysisTimeframeEnum::options())
                ->default(AnalysisTimeframeEnum::default())
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('date_from', null);
                    $set('date_to', null);
                }),
            DatePicker::make('date_from')
                ->label('Von')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->visible(fn (Get $get): bool => $get('timeframe') === AnalysisTimeframeEnum::CUSTOM->name),
            DatePicker::make('date_to')
                ->label('Bis')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->visible(fn (Get $get): bool => $get('timeframe') === AnalysisTimeframeEnum::CUSTOM->name),
            TextInput::make('currency')
                ->label('Währung')
                ->default(fn (): string => $this->defaultCurrency())
                ->maxLength(8)
                ->required(),
        ];
    }

    /**
     * @return list<int>
     */
    private function intList(mixed $value): array
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

    private function normalizeCurrency(mixed $value): string
    {
        $currency = strtoupper(trim((string) ($value ?? '')));

        return $currency !== '' ? $currency : 'EUR';
    }

    private function defaultCurrency(): string
    {
        $userId = auth()->id();

        if ($userId === null) {
            return 'EUR';
        }

        return DashboardWidgetPreference::forUser((int) $userId)->{ DashboardWidgetPreference::currency } ?? 'EUR';
    }
}

<?php

namespace App\Filament\Admin\Resources\Financial\Budgets\RelationManagers;

use App\Filament\Admin\Resources\Financial\Transactions\Schemas\TransactionInfolist;
use App\Filament\Admin\Resources\Financial\Transactions\Tables\TransactionsTable;
use App\Models\Financial\Budget;
use App\Models\Financial\Transaction;
use App\Services\Budget\BudgetCalculationService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Transaktionen, die über die verknüpften Kategorien auf das Budget einzahlen.
 *
 * Es gibt keine direkte Eloquent-Relation Budget -> Transaction (zwei Pivot-Tabellen plus
 * optionale Unterkategorien), deshalb liefert der BudgetCalculationService die Query.
 * $relationship zeigt auf die echte Kategorien-Relation, damit der RelationManager an den
 * Owner-Record gebunden bleibt; Table::query() hat Vorrang vor der Relationship-Query.
 */
class TransactionsRelationManager extends RelationManager
{
    public const string FILTER_ALL_PERIODS = 'all_periods';
    protected static string $relationship = Budget::belongs_to_many_transaction_categories;
    protected static ?string $title = 'Transaktionen';

    public function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = TransactionsTable::configure($table)
            ->recordTitleAttribute(Transaction::purpose)
            ->query(fn(): Builder => $this->buildQuery());

        return $table->filters([
            Filter::make(self::FILTER_ALL_PERIODS)
                ->label('Alle Zeiträume')
                ->toggle()
                // Der Zeitraum wird bereits in buildQuery() ausgewertet.
                ->query(static fn(Builder $query): Builder => $query),
            ...$table->getFilters(),
        ]);
    }

    private function buildQuery(): Builder
    {
        /** @var Budget $budget */
        $budget = $this->getOwnerRecord();
        $service = app(BudgetCalculationService::class);

        if ($this->showsAllPeriods()) {
            return $service->transactionQuery($budget);
        }

        ['start' => $start, 'end' => $end] = $service->periodRange($budget);

        return $service->transactionQuery($budget, $start, $end);
    }

    private function showsAllPeriods(): bool
    {
        return (bool)($this->getTableFilterState(self::FILTER_ALL_PERIODS)['isActive'] ?? false);
    }

    public function getTableHeading(): string
    {
        /** @var Budget $budget */
        $budget = $this->getOwnerRecord();

        if ($this->showsAllPeriods()) {
            return 'Transaktionen (alle Zeiträume)';
        }

        $period = app(BudgetCalculationService::class)->periodRange($budget);

        return 'Transaktionen · ' . $budget->period->formatRange($period['start']);
    }
}

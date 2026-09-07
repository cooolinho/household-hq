<?php

namespace App\Filament\App\Resources\Financial\Goals\RelationManagers;

use App\Filament\App\Resources\Financial\Transactions\Schemas\TransactionInfolist;
use App\Filament\App\Resources\Financial\Transactions\Tables\TransactionsTable;
use App\Models\Financial\Goal;
use App\Models\Financial\Transaction;
use App\Services\Goal\GoalCalculationService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Transaktionen, die über die verknüpften Kategorien auf das Ziel einzahlen.
 *
 * Es gibt keine direkte Eloquent-Relation Goal -> Transaction (zwei Pivot-Tabellen plus
 * optionale Unterkategorien), deshalb liefert der GoalCalculationService die Query.
 * $relationship zeigt auf die echte Kategorien-Relation, damit der RelationManager an den
 * Owner-Record gebunden bleibt; Table::query() hat Vorrang vor der Relationship-Query.
 *
 * Anders als beim Budget gibt es keinen Perioden-Filter – Ziele sind kumulativ ab start_date.
 */
class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = Goal::belongs_to_many_transaction_categories;
    protected static ?string $title = 'Transaktionen';

    public function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->recordTitleAttribute(Transaction::purpose)
            ->query(fn(): Builder => $this->buildQuery());
    }

    private function buildQuery(): Builder
    {
        /** @var Goal $goal */
        $goal = $this->getOwnerRecord();

        return app(GoalCalculationService::class)->transactionQuery($goal);
    }

    public function getTableHeading(): string
    {
        /** @var Goal $goal */
        $goal = $this->getOwnerRecord();

        return 'Transaktionen seit ' . $goal->start_date->format('d.m.Y');
    }
}

<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Widgets;

use App\Filament\Admin\Resources\Financial\FixedCosts\Tables\FixedCostsTable;
use App\Models\Financial\FixedCost;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FixedCostsExpensesTableWidget extends TableWidget
{
    public int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return FixedCostsTable::configure(
            table: $table->query(FixedCost::query()),
            modifyQueryUsing: fn(Builder $query): Builder => $query
                ->where(FixedCost::user_id, auth()->id())
                ->where(FixedCost::amount, '<', 0),
            amountSortDirection: 'asc',
            withToolbarActions: false,
            withAmountDirectionFilters: false,
        )
            ->heading('Fixkosten Ausgaben');
    }
}


<?php

namespace App\Filament\App\Widgets\Dashboard;

use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\App\Widgets\Dashboard\Concerns\UsesDashboardPreferences;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingTransactionsTableWidget extends TableWidget
{
    use UsesDashboardPreferences;

    public int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        $userId = $this->getDashboardUserId();
        $currency = $this->getDashboardCurrency();
        $monthStart = CarbonImmutable::today()->startOfMonth();

        return $table
            ->heading('Letzte Transaktionen diesen Monat (' . $currency . ')')
            ->query(
                Transaction::query()
                    ->where(Transaction::user_id, $userId)
                    ->where(Transaction::date, '>=', $monthStart->toDateString())
                    ->whereRaw('UPPER(' . Transaction::amount_currency . ') = ?', [$currency])
                    ->latest(Transaction::date)
                    ->limit(10),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make(Transaction::date)
                    ->label('Datum')
                    ->date('d.m.Y'),
                TextColumn::make(Transaction::payer)
                    ->label('Auftraggeber')
                    ->limit(20)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make(Transaction::purpose)
                    ->label('Verwendungszweck')
                    ->limit(35)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::belongs_to_fixed_cost . '.' . FixedCost::name)
                    ->label('Fixkosten')
                    ->badge()
                    ->placeholder('-')
                    ->url(fn(Transaction $record): ?string => $record->fixed_cost_id
                        ? FixedCostResource::getViewUrl((int)$record->fixed_cost_id)
                        : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::amount)
                    ->label('Betrag')
                    ->alignEnd()
                    ->color(fn(Transaction $record): string => ((float)$record->amount) < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn(Transaction $record): string => number_format((float)$record->amount, 2, ',', '.') . ' ' . $currency),
            ]);
    }
}


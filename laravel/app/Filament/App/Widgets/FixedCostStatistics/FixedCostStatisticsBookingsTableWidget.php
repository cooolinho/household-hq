<?php

namespace App\Filament\App\Widgets\FixedCostStatistics;

use App\Filament\App\Widgets\FixedCostStatistics\Concerns\InteractsWithFixedCostStatisticsFilters;
use App\Services\FixedCost\ProjectedBooking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Auf die tatsächlichen Buchungstermine projizierte Fixkosten-Buchungen im gewählten Zeitraum.
 * Budgets erscheinen hier nicht, da sie keine Buchungstermine haben (nur monatlich normalisiert).
 */
class FixedCostStatisticsBookingsTableWidget extends TableWidget
{
    use InteractsWithFixedCostStatisticsFilters;

    public int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $userId = (int)auth()->id();
        ['start' => $start, 'end' => $end] = $this->resolveRange();

        $bookings = $this->balanceService()->projectedBookings($userId, $start, $end);

        $income = array_sum(array_map(
            fn(ProjectedBooking $b): float => $b->isIncome() ? $b->amount : 0.0,
            $bookings,
        ));
        $expenses = abs(array_sum(array_map(
            fn(ProjectedBooking $b): float => $b->isIncome() ? 0.0 : $b->amount,
            $bookings,
        )));

        return $table
            ->records(fn(): array => collect($bookings)
                ->mapWithKeys(fn(ProjectedBooking $b): array => [
                    $b->fixedCost->getKey() . '-' . $b->date->toDateString() => [
                        'date' => $b->date->toDateString(),
                        'name' => $b->fixedCost->name,
                        'category' => $b->categoryLabel(),
                        'amount' => $b->amount,
                    ],
                ])
                ->all())
            ->heading('Buchungen im Zeitraum')
            ->description(sprintf(
                'Einnahmen %s · Ausgaben %s · Saldo %s',
                $this->formatMoney($income),
                $this->formatMoney($expenses),
                $this->formatMoney($income - $expenses, true),
            ))
            ->columns([
                TextColumn::make('date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Kategorie')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Betrag')
                    ->formatStateUsing(fn(array $record): string => $this->formatMoney((float)$record['amount'], true))
                    ->color(fn(array $record): string => ((float)$record['amount']) < 0 ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->defaultSort('date')
            ->paginated([25, 50, 100]);
    }

    private function formatMoney(float $value, bool $signed = false): string
    {
        return ($signed && $value > 0 ? '+' : '') . number_format($value, 2, ',', '.') . ' €';
    }
}

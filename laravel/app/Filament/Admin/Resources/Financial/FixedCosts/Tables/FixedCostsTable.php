<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Tables;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Models\Enums\FixedCostCategoryEnum;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FixedCostsTable
{
    public static function configure(
        Table    $table,
        ?Closure $modifyQueryUsing = null,
        string   $amountSortDirection = 'desc',
        bool     $withToolbarActions = true,
        bool     $withAmountDirectionFilters = true,
    ): Table
    {
        $table = $table
            ->defaultSort(FixedCost::amount, $amountSortDirection)
            ->columns([
                TextColumn::make(FixedCost::name)
                    ->label('Name')
                    ->searchable(),
                TextColumn::make(FixedCost::amount)
                    ->label('Betrag')
                    ->formatStateUsing(fn(FixedCost $record): string => number_format((float)$record->amount, 2, ',', '.') . ' EUR')
                    ->color(fn(FixedCost $record): string => ((float)$record->amount) < 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make(FixedCost::category)
                    ->label('Kategorie')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => FixedCostCategoryEnum::tryFrom((string)$state)?->label() ?? (string)$state),
                TextColumn::make(FixedCost::interval)
                    ->label('Intervall')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => FixedCostIntervalEnum::tryFrom((string)$state)?->label() ?? (string)$state),
                TextColumn::make(FixedCost::ends_mode)
                    ->label('Endmodus')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => FixedCostEndsModeEnum::tryFrom((string)$state)?->label() ?? (string)$state),
                TextColumn::make(FixedCost::next_booking_date)
                    ->label('Nächste Buchung')
                    ->date()
                    ->sortable(),
                TextColumn::make(FixedCost::extended_interval)
                    ->label('Verl. Intervall')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => FixedCostIntervalEnum::tryFrom((string)$state)?->label() ?? (string)$state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(FixedCost::ends_date)
                    ->label('Enddatum')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(FixedCost::extended_date)
                    ->label('Verlängert bis')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(FixedCost::created_at)
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('show')
                        ->label('Anzeigen')
                        ->color('secondary')
                        ->icon(Heroicon::Eye)
                        ->url(fn(FixedCost $record): string => FixedCostResource::getViewUrl($record->id)),
                    EditAction::make(),
                ])
            ]);

        $filters = [
            SelectFilter::make(FixedCost::category)
                ->label('Kategorie')
                ->options(FixedCostCategoryEnum::options())
                ->searchable(),
            SelectFilter::make(FixedCost::interval)
                ->label('Intervall')
                ->options(FixedCostIntervalEnum::options()),
            SelectFilter::make(FixedCost::ends_mode)
                ->label('Endmodus')
                ->options(FixedCostEndsModeEnum::options()),
        ];

        if ($withAmountDirectionFilters) {
            $filters[] = SelectFilter::make('amount_type')
                ->label('Typ')
                ->options([
                    'expense' => 'Ausgaben',
                    'income' => 'Einnahmen',
                ])
                ->query(function (Builder $query, $value): Builder {
                    return match ($value) {
                        'expense' => $query->where(FixedCost::amount, '<', 0),
                        'income' => $query->where(FixedCost::amount, '>', 0),
                        default => $query,
                    };
                });
        }

        $table->filters($filters);

        if ($modifyQueryUsing !== null) {
            $table->modifyQueryUsing($modifyQueryUsing);
        }

        if ($withToolbarActions) {
            $table->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
        }

        return $table;
    }
}

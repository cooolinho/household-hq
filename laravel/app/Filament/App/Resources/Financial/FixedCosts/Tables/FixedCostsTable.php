<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Tables;

use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostCategory;
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
        Table  $table,
        ?Closure $modifyQueryUsing = null,
        string $amountSortDirection = 'desc',
        bool   $withToolbarActions = true,
        bool   $withAmountDirectionFilters = true,
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
                TextColumn::make('category.' . FixedCostCategory::name)
                    ->label('Kategorie')
                    ->badge()
                    ->placeholder('Nicht kategorisiert'),
                TextColumn::make(FixedCost::interval)
                    ->label('Intervall')
                    ->badge()
                    ->formatStateUsing(fn(FixedCost $record): string => self::formatInterval($record)),
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
                    ->formatStateUsing(fn(FixedCost $record): string => self::formatInterval($record, true))
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
                ]),
            ]);

        $filters = [
            SelectFilter::make(FixedCost::category_id)
                ->label('Kategorie')
                ->options(fn() => FixedCostCategory::query()
                    ->orderBy(FixedCostCategory::group)
                    ->orderBy(FixedCostCategory::name)
                    ->pluck(FixedCostCategory::name, FixedCostCategory::id)
                    ->toArray())
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

    private static function formatInterval(FixedCost $record, bool $extended = false): string
    {
        $interval = (string)($extended
            ? $record->{FixedCost::extended_interval}
            : $record->{FixedCost::interval});
        $intervalEnum = FixedCostIntervalEnum::tryFrom($interval);

        if ($interval !== FixedCostIntervalEnum::CUSTOM->name) {
            return $intervalEnum?->label() ?? $interval;
        }

        $value = $extended
            ? $record->{FixedCost::custom_extended_interval_value}
            : $record->{FixedCost::custom_interval_value};
        $unit = $extended
            ? $record->{FixedCost::custom_extended_interval_unit}
            : $record->{FixedCost::custom_interval_unit};
        $unitLabel = FixedCostIntervalUnitEnum::tryFrom((string)$unit)?->label();

        return $value !== null && $unitLabel !== null
            ? sprintf('Alle %d %s', $value, $unitLabel)
            : FixedCostIntervalEnum::CUSTOM->label();
    }
}

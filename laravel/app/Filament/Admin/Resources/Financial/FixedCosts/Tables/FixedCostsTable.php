<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Tables;

use App\Models\Financial\FixedCost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FixedCostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(FixedCost::name)
                    ->searchable(),
                TextColumn::make(FixedCost::amount)
                    ->numeric()
                    ->sortable(),
                TextColumn::make(FixedCost::category)
                    ->badge(),
                TextColumn::make(FixedCost::interval)
                    ->badge(),
                TextColumn::make(FixedCost::ends_mode)
                    ->badge(),
                TextColumn::make(FixedCost::ends_date)
                    ->date()
                    ->sortable(),
                TextColumn::make(FixedCost::ends_interval)
                    ->badge(),
                TextColumn::make(FixedCost::extended_date)
                    ->date()
                    ->sortable(),
                TextColumn::make(FixedCost::extended_interval)
                    ->badge(),
                TextColumn::make(FixedCost::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(FixedCost::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

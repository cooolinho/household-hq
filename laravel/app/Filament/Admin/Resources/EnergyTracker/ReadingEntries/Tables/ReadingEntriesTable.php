<?php

namespace App\Filament\Admin\Resources\EnergyTracker\ReadingEntries\Tables;

use App\Models\EnergyTracker\ReadingEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReadingEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(ReadingEntry::reading_value)
                    ->searchable(),
                TextColumn::make(ReadingEntry::reading_date)
                    ->dateTime()
                    ->sortable(),
                TextColumn::make(ReadingEntry::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(ReadingEntry::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

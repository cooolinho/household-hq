<?php

namespace App\Filament\Admin\Resources\CSVImportProfiles\Tables;

use App\Models\CSVImportProfile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CSVImportProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(CSVImportProfile::name)
                    ->searchable(),
                TextColumn::make(CSVImportProfile::bank)
                    ->searchable(),
                TextColumn::make(CSVImportProfile::delimiter)
                    ->searchable(),
                TextColumn::make(CSVImportProfile::enclosure)
                    ->searchable(),
                TextColumn::make(CSVImportProfile::escape)
                    ->searchable(),
                TextColumn::make(CSVImportProfile::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(CSVImportProfile::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\App\Resources\Financial\CSVImportProfiles\Tables;

use App\Filament\App\Pages\TransactionCsvImportPage;
use App\Models\Financial\CSVImportProfile;
use App\Services\TransactionImport\AmountFormat;
use App\Services\TransactionImport\CsvEncoding;
use App\Services\TransactionImport\CsvFormat;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CSVImportProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(CSVImportProfile::name)
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make(CSVImportProfile::bank)
                    ->label('Bank')
                    ->searchable(),
                TextColumn::make(CSVImportProfile::delimiter)
                    ->label('Trennzeichen')
                    ->formatStateUsing(fn (?string $state) => CsvFormat::delimiterOptions()[$state] ?? $state),
                TextColumn::make(CSVImportProfile::encoding)
                    ->label('Kodierung')
                    ->formatStateUsing(fn (?string $state) => CsvEncoding::tryFrom((string) $state)?->label() ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(CSVImportProfile::amount_format)
                    ->label('Betragsformat')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => (AmountFormat::tryFrom((string) $state) ?? AmountFormat::German)->label()),
                TextColumn::make(CSVImportProfile::header_mapping)
                    ->label('Zugeordnete Felder')
                    ->state(fn (CSVImportProfile $record): int => count(array_filter(
                        $record->mapping ?? [],
                        fn ($index) => $index !== null && $index !== '',
                    ))),
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
                Action::make('import')
                    ->label('Importieren')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('success')
                    ->url(fn (CSVImportProfile $record): string => TransactionCsvImportPage::getUrl(['profile' => $record->id])),
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

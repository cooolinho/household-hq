<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\RelationManagers;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Schemas\MeasurementDeviceContractPriceForm;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = MeasurementDeviceContract::has_many_prices;

    public function form(Schema $schema): Schema
    {
        return MeasurementDeviceContractPriceForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(MeasurementDeviceContractPrice::valid_from)
            ->columns([
                TextColumn::make(MeasurementDeviceContractPrice::valid_from)
                    ->label('Gültig ab')
                    ->date()
                    ->sortable(),
                TextColumn::make(MeasurementDeviceContractPrice::unit_price)
                    ->label('Preis pro Einheit')
                    ->numeric(decimalPlaces: 6)
                    ->sortable(),
                TextColumn::make(MeasurementDeviceContractPrice::notes)
                    ->label('Notizen')
                    ->placeholder('-')
                    ->limit(60),
                TextColumn::make(MeasurementDeviceContractPrice::created_at)
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
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

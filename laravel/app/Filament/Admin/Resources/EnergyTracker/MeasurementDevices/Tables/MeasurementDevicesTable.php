<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Tables;

use App\Models\EnergyTracker\MeasurementDevice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeasurementDevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(MeasurementDevice::name)
                    ->searchable(),
                TextColumn::make(MeasurementDevice::group)
                    ->searchable(),
                TextColumn::make(MeasurementDevice::counting_type)
                    ->badge(),
                TextColumn::make(MeasurementDevice::counting_method)
                    ->badge(),
                TextColumn::make(MeasurementDevice::counting_unit)
                    ->badge(),
                TextColumn::make(MeasurementDevice::meter_reading_value)
                    ->searchable(),
                TextColumn::make(MeasurementDevice::meter_reading_date)
                    ->date()
                    ->sortable(),
                TextColumn::make(MeasurementDevice::meter_description)
                    ->searchable(),
                TextColumn::make(MeasurementDevice::has_many_contracts . '_count')
                    ->label('Verträge')
                    ->counts(MeasurementDevice::has_many_contracts)
                    ->badge()
                    ->color('info'),
                TextColumn::make(MeasurementDevice::decimal_places)
                    ->numeric()
                    ->sortable(),
                TextColumn::make(MeasurementDevice::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(MeasurementDevice::updated_at)
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

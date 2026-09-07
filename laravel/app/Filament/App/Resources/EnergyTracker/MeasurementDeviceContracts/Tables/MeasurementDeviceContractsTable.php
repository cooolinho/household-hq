<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Tables;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Enums\EnergyTrackerContractBasePriceIntervalEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MeasurementDeviceContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(MeasurementDeviceContract::name)
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make(
                    MeasurementDeviceContract::belongs_to_measurement_device . '.' . MeasurementDevice::name,
                )
                    ->label('Messgerät')
                    ->searchable()
                    ->sortable(),
                TextColumn::make(MeasurementDeviceContract::provider)
                    ->label('Anbieter')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make(MeasurementDeviceContract::contract_number)
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make(MeasurementDeviceContract::is_active)
                    ->label('Aktiv')
                    ->boolean(),
                TextColumn::make(MeasurementDeviceContract::base_price)
                    ->label('Grundpreis')
                    ->formatStateUsing(function (mixed $state, MeasurementDeviceContract $record): string {
                        if ($state === null) {
                            return '-';
                        }

                        $interval = EnergyTrackerContractBasePriceIntervalEnum::tryFromName(
                            $record->{MeasurementDeviceContract::base_price_interval},
                        )?->label() ?? '';

                        return number_format((float)$state, 4, ',', '.') . ' '
                            . $record->{MeasurementDeviceContract::currency} . ($interval ? ' / ' . $interval : '');
                    })
                    ->sortable(),
                TextColumn::make(MeasurementDeviceContract::has_many_prices . '_count')
                    ->label('Preise')
                    ->counts(MeasurementDeviceContract::has_many_prices)
                    ->badge()
                    ->color('info'),
                TextColumn::make(MeasurementDeviceContract::has_many_documents . '_count')
                    ->label('Dokumente')
                    ->counts(MeasurementDeviceContract::has_many_documents)
                    ->badge()
                    ->color('gray'),
                TextColumn::make(MeasurementDeviceContract::belongs_to_many_contacts . '_count')
                    ->label('Kontakte')
                    ->counts(MeasurementDeviceContract::belongs_to_many_contacts)
                    ->badge()
                    ->color('gray'),
                TextColumn::make(MeasurementDeviceContract::starts_on)
                    ->label('Beginn')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(MeasurementDeviceContract::ends_on)
                    ->label('Ende')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make(MeasurementDeviceContract::is_active)
                    ->label('Aktiv'),
                SelectFilter::make(MeasurementDeviceContract::measurement_device_id)
                    ->label('Messgerät')
                    ->options(fn(): array => MeasurementDevice::query()
                        ->where(MeasurementDevice::user_id, auth()->id())
                        ->orderBy(MeasurementDevice::name)
                        ->pluck(MeasurementDevice::name, MeasurementDevice::id)
                        ->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('activate')
                    ->label('Aktivieren')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(MeasurementDeviceContract $record): bool => !$record->isActive())
                    ->requiresConfirmation()
                    ->action(fn(MeasurementDeviceContract $record): bool => $record->activate()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

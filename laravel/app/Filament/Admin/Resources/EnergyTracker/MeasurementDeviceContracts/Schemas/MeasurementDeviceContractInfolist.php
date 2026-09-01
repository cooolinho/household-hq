<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Schemas;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Enums\EnergyTrackerContractBasePriceIntervalEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MeasurementDeviceContractInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('measurement_device_name')
                    ->label('Messgerät')
                    ->state(fn(MeasurementDeviceContract $record): string => (string)($record->measurementDevice?->{MeasurementDevice::name} ?? '-')),
                TextEntry::make(MeasurementDeviceContract::name)
                    ->label('Bezeichnung'),
                TextEntry::make(MeasurementDeviceContract::provider)
                    ->label('Anbieter')
                    ->placeholder('-'),
                TextEntry::make(MeasurementDeviceContract::contract_number)
                    ->label('Vertragsnummer')
                    ->placeholder('-'),
                TextEntry::make(MeasurementDeviceContract::starts_on)
                    ->label('Vertragsbeginn')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(MeasurementDeviceContract::ends_on)
                    ->label('Vertragsende')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(MeasurementDeviceContract::is_active)
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(mixed $state): string => $state ? 'Aktiv' : 'Inaktiv')
                    ->color(fn(mixed $state): string => $state ? 'success' : 'gray'),
                TextEntry::make(MeasurementDeviceContract::base_price)
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
                    ->placeholder('-'),
                TextEntry::make(MeasurementDeviceContract::notes)
                    ->label('Notizen')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make(MeasurementDeviceContract::created_at)
                    ->label('Erstellt')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(MeasurementDeviceContract::updated_at)
                    ->label('Aktualisiert')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

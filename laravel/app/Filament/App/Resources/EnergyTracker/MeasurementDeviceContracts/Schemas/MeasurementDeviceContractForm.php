<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Schemas;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Enums\EnergyTrackerContractBasePriceIntervalEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MeasurementDeviceContractForm
{
    public static function configure(Schema $schema, ?MeasurementDevice $ownerDevice = null): Schema
    {
        $deviceField = $ownerDevice
            ? Hidden::make(MeasurementDeviceContract::measurement_device_id)
                ->default($ownerDevice->getKey())
                ->required()
            : Select::make(MeasurementDeviceContract::measurement_device_id)
                ->label('Messgerät')
                ->options(fn(): array => MeasurementDevice::query()
                    ->where(MeasurementDevice::user_id, auth()->id())
                    ->orderBy(MeasurementDevice::name)
                    ->pluck(MeasurementDevice::name, MeasurementDevice::id)
                    ->toArray())
                ->searchable()
                ->preload()
                ->required();

        return $schema
            ->components([
                Section::make('Vertrag')
                    ->schema([
                        $deviceField,
                        TextInput::make(MeasurementDeviceContract::name)
                            ->label('Bezeichnung')
                            ->required()
                            ->maxLength(255),
                        TextInput::make(MeasurementDeviceContract::provider)
                            ->label('Anbieter')
                            ->maxLength(255),
                        TextInput::make(MeasurementDeviceContract::contract_number)
                            ->label('Vertragsnummer')
                            ->maxLength(255),
                        DatePicker::make(MeasurementDeviceContract::starts_on)
                            ->label('Vertragsbeginn'),
                        DatePicker::make(MeasurementDeviceContract::ends_on)
                            ->label('Vertragsende')
                            ->after(MeasurementDeviceContract::starts_on),
                        Toggle::make(MeasurementDeviceContract::is_active)
                            ->label('Aktiver Vertrag')
                            ->default(false)
                            ->helperText('Beim Aktivieren werden andere Verträge dieses Messgeräts deaktiviert.'),
                    ])
                    ->columns(2),
                Section::make('Grundpreis')
                    ->description('Wiederkehrende Anschluss- oder Nutzungsgebühr unabhängig vom Verbrauch.')
                    ->schema([
                        TextInput::make(MeasurementDeviceContract::base_price)
                            ->label('Grundpreis')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->prefix('€'),
                        Select::make(MeasurementDeviceContract::base_price_interval)
                            ->label('Abrechnungsintervall')
                            ->options(EnergyTrackerContractBasePriceIntervalEnum::options())
                            ->default(EnergyTrackerContractBasePriceIntervalEnum::default())
                            ->required(fn(Get $get): bool => filled($get(MeasurementDeviceContract::base_price))),
                        TextInput::make(MeasurementDeviceContract::currency)
                            ->label('Währung')
                            ->default('EUR')
                            ->required()
                            ->maxLength(3)
                            ->minLength(3)
                            ->dehydrateStateUsing(fn(?string $state): string => strtoupper((string)$state)),
                    ])
                    ->columns(3),
                Textarea::make(MeasurementDeviceContract::notes)
                    ->label('Notizen')
                    ->columnSpanFull(),
            ]);
    }
}

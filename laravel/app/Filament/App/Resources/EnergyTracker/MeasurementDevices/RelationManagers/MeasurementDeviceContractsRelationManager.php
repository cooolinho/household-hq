<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\RelationManagers;

use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\MeasurementDeviceContractResource;
use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Schemas\MeasurementDeviceContractForm;
use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Tables\MeasurementDeviceContractsTable;
use App\Models\EnergyTracker\MeasurementDevice;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MeasurementDeviceContractsRelationManager extends RelationManager
{
    protected static string $relationship = MeasurementDevice::has_many_contracts;

    protected static ?string $relatedResource = MeasurementDeviceContractResource::class;

    public function form(Schema $schema): Schema
    {
        return MeasurementDeviceContractForm::configure($schema, $this->ownerRecord);
    }

    public function infolist(Schema $schema): Schema
    {
        return MeasurementDeviceContractResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return MeasurementDeviceContractsTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}

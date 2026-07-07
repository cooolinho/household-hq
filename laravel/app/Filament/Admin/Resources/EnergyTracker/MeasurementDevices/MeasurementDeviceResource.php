<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages\CreateMeasurementDevice;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages\EditMeasurementDevice;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages\ListMeasurementDevices;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages\ViewMeasurementDevice;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\RelationManagers\ReadingEntriesRelationManager;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Schemas\MeasurementDeviceForm;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Schemas\MeasurementDeviceInfolist;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Tables\MeasurementDevicesTable;
use App\Models\EnergyTracker\MeasurementDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MeasurementDeviceResource extends Resource
{
    protected static ?string $model = MeasurementDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Energy Tracker';
    protected static ?string $navigationLabel = 'Messgeräte';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MeasurementDeviceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeasurementDeviceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeasurementDevicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReadingEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeasurementDevices::route('/'),
            'create' => CreateMeasurementDevice::route('/create'),
            'view' => ViewMeasurementDevice::route('/{record}'),
            'edit' => EditMeasurementDevice::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof MeasurementDevice) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

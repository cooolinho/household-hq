<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices;

use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages\CreateMeasurementDevice;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages\EditMeasurementDevice;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages\ListMeasurementDevices;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages\MeasurementDeviceReadingWizard;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages\ViewMeasurementDevice;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\RelationManagers\MeasurementDeviceContractsRelationManager;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\RelationManagers\ReadingEntriesRelationManager;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Schemas\MeasurementDeviceForm;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Schemas\MeasurementDeviceInfolist;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Tables\MeasurementDevicesTable;
use App\Menu\NavigationGroup;
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
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::ENERGY_TRACKER;
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.measurement_device.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.measurement_device.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.measurement_device.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(MeasurementDevice::user_id, auth()->id())->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

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
        return MeasurementDevicesTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(MeasurementDevice::user_id, auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [
            ReadingEntriesRelationManager::class,
            MeasurementDeviceContractsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeasurementDevices::route('/'),
            'wizard' => MeasurementDeviceReadingWizard::route('/wizard'),
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

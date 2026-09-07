<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Pages\CreateMeasurementDeviceContract;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Pages\EditMeasurementDeviceContract;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Pages\ListMeasurementDeviceContracts;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Pages\ViewMeasurementDeviceContract;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\RelationManagers\ContactsRelationManager;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\RelationManagers\DocumentsRelationManager;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\RelationManagers\PricesRelationManager;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Schemas\MeasurementDeviceContractForm;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Schemas\MeasurementDeviceContractInfolist;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Tables\MeasurementDeviceContractsTable;
use App\Filament\Admin\Resources\Reminders\RelationManagers\RemindersRelationManager;
use App\Menu\NavigationGroup;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MeasurementDeviceContractResource extends Resource
{
    protected static ?string $model = MeasurementDeviceContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::ENERGY_TRACKER;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = MeasurementDeviceContract::name;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.measurement_device_contract.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.measurement_device_contract.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.measurement_device_contract.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereHas(
                MeasurementDeviceContract::belongs_to_measurement_device,
                fn(Builder $query) => $query->where(MeasurementDevice::user_id, auth()->id()),
            )
            ->count();

        return $count > 0 ? (string)$count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas(
                MeasurementDeviceContract::belongs_to_measurement_device,
                fn(Builder $query) => $query->where(MeasurementDevice::user_id, auth()->id()),
            )
            ->with(MeasurementDeviceContract::belongs_to_measurement_device);
    }

    public static function form(Schema $schema): Schema
    {
        return MeasurementDeviceContractForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeasurementDeviceContractInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeasurementDeviceContractsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
            DocumentsRelationManager::class,
            ContactsRelationManager::class,
            RemindersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeasurementDeviceContracts::route('/'),
            'create' => CreateMeasurementDeviceContract::route('/create'),
            'view' => ViewMeasurementDeviceContract::route('/{record}'),
            'edit' => EditMeasurementDeviceContract::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof MeasurementDeviceContract
            && $record->measurementDevice?->{MeasurementDevice::user_id} === auth()->id();
    }
}

<?php

namespace App\Filament\Admin\Resources\Inventory\Locations;

use App\Filament\Admin\Resources\Inventory\Locations\Pages\CreateLocation;
use App\Filament\Admin\Resources\Inventory\Locations\Pages\EditLocation;
use App\Filament\Admin\Resources\Inventory\Locations\Pages\ListLocations;
use App\Filament\Admin\Resources\Inventory\Locations\Pages\ViewLocation;
use App\Filament\Admin\Resources\Inventory\Locations\RelationManagers\ArticlesRelationManager;
use App\Filament\Admin\Resources\Inventory\Locations\Schemas\LocationForm;
use App\Filament\Admin\Resources\Inventory\Locations\Schemas\LocationInfolist;
use App\Filament\Admin\Resources\Inventory\Locations\Tables\LocationsTable;
use App\Filament\Admin\Resources\Inventory\Support\InventoryNavigationVisibility;
use App\Menu\NavigationGroup;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::INVENTORY;
    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = Location::name;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.location.navigation_label');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return InventoryNavigationVisibility::hasArticlesForCurrentUser();
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.location.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.location.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereHas(
            Location::belongs_to_collection,
            fn($q) => $q->where(Collection::user_id, auth()->id())
        )->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LocationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->whereHas(
                    Location::belongs_to_collection,
                    fn($collectionQuery) => $collectionQuery->where(Collection::user_id, auth()->id())
                );
            });
    }

    public static function getRelations(): array
    {
        return [
            ArticlesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'view' => ViewLocation::route('/{record}'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Location) {
            return $record->collection?->user_id === auth()->id();
        }

        return false;
    }
}

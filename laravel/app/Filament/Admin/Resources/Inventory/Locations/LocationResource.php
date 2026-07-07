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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|null|\UnitEnum $navigationGroup = 'Inventar';
    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = Location::name;

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
        return LocationsTable::configure($table);
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

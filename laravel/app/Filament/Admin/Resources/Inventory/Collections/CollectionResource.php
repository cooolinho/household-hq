<?php

namespace App\Filament\Admin\Resources\Inventory\Collections;

use App\Filament\Admin\Resources\Inventory\Collections\Pages\CreateCollection;
use App\Filament\Admin\Resources\Inventory\Collections\Pages\EditCollection;
use App\Filament\Admin\Resources\Inventory\Collections\Pages\ListCollections;
use App\Filament\Admin\Resources\Inventory\Collections\Pages\ViewCollection;
use App\Filament\Admin\Resources\Inventory\Collections\RelationManagers\LocationsRelationManager;
use App\Filament\Admin\Resources\Inventory\Collections\Schemas\CollectionForm;
use App\Filament\Admin\Resources\Inventory\Collections\Schemas\CollectionInfolist;
use App\Filament\Admin\Resources\Inventory\Collections\Tables\CollectionsTable;
use App\Models\Inventory\Collection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Inventar';
    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = Collection::name;

    public static function form(Schema $schema): Schema
    {
        return CollectionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CollectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'view' => ViewCollection::route('/{record}'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }
}

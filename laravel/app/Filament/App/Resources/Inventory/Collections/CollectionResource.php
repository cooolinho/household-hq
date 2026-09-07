<?php

namespace App\Filament\App\Resources\Inventory\Collections;

use App\Filament\App\Resources\Inventory\Collections\Pages\CreateCollection;
use App\Filament\App\Resources\Inventory\Collections\Pages\EditCollection;
use App\Filament\App\Resources\Inventory\Collections\Pages\ListCollections;
use App\Filament\App\Resources\Inventory\Collections\Pages\ViewCollection;
use App\Filament\App\Resources\Inventory\Collections\RelationManagers\ArticlesRelationManager;
use App\Filament\App\Resources\Inventory\Collections\RelationManagers\LocationsRelationManager;
use App\Filament\App\Resources\Inventory\Collections\Schemas\CollectionForm;
use App\Filament\App\Resources\Inventory\Collections\Schemas\CollectionInfolist;
use App\Filament\App\Resources\Inventory\Collections\Tables\CollectionsTable;
use App\Filament\App\Resources\Inventory\Support\InventoryNavigationVisibility;
use App\Menu\NavigationGroup;
use App\Models\Inventory\Collection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::INVENTORY;
    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = Collection::name;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.collection.navigation_label');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return InventoryNavigationVisibility::hasArticlesForCurrentUser();
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.collection.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.collection.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(Collection::user_id, auth()->id())->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

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
        return CollectionsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(Collection::user_id, auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [
            LocationsRelationManager::class,
            ArticlesRelationManager::class,
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

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Collection) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

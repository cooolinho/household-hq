<?php

namespace App\Filament\Admin\Resources\Financial\FixedCostCategories;

use App\Filament\Admin\Resources\Financial\FixedCostCategories\Pages\CreateFixedCostCategory;
use App\Filament\Admin\Resources\Financial\FixedCostCategories\Pages\EditFixedCostCategory;
use App\Filament\Admin\Resources\Financial\FixedCostCategories\Pages\ListFixedCostCategories;
use App\Filament\Admin\Resources\Financial\FixedCostCategories\RelationManagers\FixedCostsRelationManager;
use App\Filament\Admin\Resources\Financial\FixedCostCategories\Schemas\FixedCostCategoryForm;
use App\Filament\Admin\Resources\Financial\FixedCostCategories\Tables\FixedCostCategoriesTable;
use App\Menu\NavigationGroup;
use App\Models\Financial\FixedCostCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FixedCostCategoryResource extends Resource
{
    protected static ?string $model = FixedCostCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::FIXED_COSTS;
    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return FixedCostCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FixedCostCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFixedCostCategories::route('/'),
            'create' => CreateFixedCostCategory::route('/create'),
            'edit' => EditFixedCostCategory::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            FixedCostsRelationManager::class,
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.fixed_cost_category.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.fixed_cost_category.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.fixed_cost_category.plural_model_label');
    }
}


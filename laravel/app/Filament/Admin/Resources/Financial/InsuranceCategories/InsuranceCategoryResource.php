<?php

namespace App\Filament\Admin\Resources\Financial\InsuranceCategories;

use App\Filament\Admin\Resources\Financial\InsuranceCategories\Pages\CreateInsuranceCategory;
use App\Filament\Admin\Resources\Financial\InsuranceCategories\Pages\EditInsuranceCategory;
use App\Filament\Admin\Resources\Financial\InsuranceCategories\Pages\ListInsuranceCategories;
use App\Filament\Admin\Resources\Financial\InsuranceCategories\RelationManagers\InsurancesRelationManager;
use App\Filament\Admin\Resources\Financial\InsuranceCategories\Schemas\InsuranceCategoryForm;
use App\Filament\Admin\Resources\Financial\InsuranceCategories\Tables\InsuranceCategoriesTable;
use App\Menu\NavigationGroup;
use App\Models\Financial\InsuranceCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InsuranceCategoryResource extends Resource
{
    protected static ?string $model = InsuranceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::INSURANCES;
    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return InsuranceCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsuranceCategories::route('/'),
            'create' => CreateInsuranceCategory::route('/create'),
            'edit' => EditInsuranceCategory::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            InsurancesRelationManager::class,
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.insurance_category.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.insurance_category.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.insurance_category.plural_model_label');
    }
}


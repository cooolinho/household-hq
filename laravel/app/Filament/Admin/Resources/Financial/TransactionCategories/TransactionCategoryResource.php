<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories;

use App\Filament\Admin\Resources\Financial\TransactionCategories\Pages\CreateTransactionCategory;
use App\Filament\Admin\Resources\Financial\TransactionCategories\Pages\EditTransactionCategory;
use App\Filament\Admin\Resources\Financial\TransactionCategories\Pages\ListTransactionCategories;
use App\Filament\Admin\Resources\Financial\TransactionCategories\Schemas\TransactionCategoryForm;
use App\Filament\Admin\Resources\Financial\TransactionCategories\Tables\TransactionCategoriesTable;
use App\Menu\NavigationGroup;
use App\Models\Financial\TransactionCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionCategoryResource extends Resource
{
    protected static ?string $model = TransactionCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::BANKS;
    protected static ?int $navigationSort = 45;

    public static function getNavigationLabel(): string
    {
        return 'Transaktionskategorien';
    }

    public static function getModelLabel(): string
    {
        return 'Transaktionskategorie';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Transaktionskategorien';
    }

    public static function form(Schema $schema): Schema
    {
        return TransactionCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionCategoriesTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $query->where(TransactionCategory::user_id, auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactionCategories::route('/'),
            'create' => CreateTransactionCategory::route('/create'),
            'edit' => EditTransactionCategory::route('/{record}/edit'),
        ];
    }
}

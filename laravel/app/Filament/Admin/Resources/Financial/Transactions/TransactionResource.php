<?php

namespace App\Filament\Admin\Resources\Financial\Transactions;

use App\Filament\Admin\Resources\Financial\Transactions\Pages\EditTransaction;
use App\Filament\Admin\Resources\Financial\Transactions\Pages\ListTransactions;
use App\Filament\Admin\Resources\Financial\Transactions\Pages\ViewTransaction;
use App\Filament\Admin\Resources\Financial\Transactions\Schemas\TransactionForm;
use App\Filament\Admin\Resources\Financial\Transactions\Schemas\TransactionInfolist;
use App\Filament\Admin\Resources\Financial\Transactions\Tables\TransactionsTable;
use App\Models\Financial\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string|UnitEnum|null $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 40;
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.transaction.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.transaction.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.transaction.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(Transaction::user_id, auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
            'edit' => EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Transaction) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

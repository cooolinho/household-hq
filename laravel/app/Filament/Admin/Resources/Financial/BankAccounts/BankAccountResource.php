<?php

namespace App\Filament\Admin\Resources\Financial\BankAccounts;

use App\Filament\Admin\Resources\Financial\BankAccounts\Pages\CreateBankAccount;
use App\Filament\Admin\Resources\Financial\BankAccounts\Pages\EditBankAccount;
use App\Filament\Admin\Resources\Financial\BankAccounts\Pages\ListBankAccounts;
use App\Filament\Admin\Resources\Financial\BankAccounts\Pages\ViewBankAccount;
use App\Filament\Admin\Resources\Financial\BankAccounts\RelationManagers\TransactionsRelationManager;
use App\Filament\Admin\Resources\Financial\BankAccounts\Schemas\BankAccountForm;
use App\Filament\Admin\Resources\Financial\BankAccounts\Schemas\BankAccountInfolist;
use App\Filament\Admin\Resources\Financial\BankAccounts\Tables\BankAccountsTable;
use App\Models\Financial\BankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static string|null|\UnitEnum $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = BankAccount::name;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.bank_account.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.bank_account.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.bank_account.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(BankAccount::user_id, auth()->id())->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return BankAccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BankAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankAccountsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(BankAccount::user_id, auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankAccounts::route('/'),
            'create' => CreateBankAccount::route('/create'),
            'view' => ViewBankAccount::route('/{record}'),
            'edit' => EditBankAccount::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof BankAccount) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

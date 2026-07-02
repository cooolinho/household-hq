<?php

namespace App\Filament\Admin\Resources\BankAccounts;

use App\Filament\Admin\Resources\BankAccounts\Pages\CreateBankAccount;
use App\Filament\Admin\Resources\BankAccounts\Pages\EditBankAccount;
use App\Filament\Admin\Resources\BankAccounts\Pages\ListBankAccounts;
use App\Filament\Admin\Resources\BankAccounts\Pages\ViewBankAccount;
use App\Filament\Admin\Resources\BankAccounts\Schemas\BankAccountForm;
use App\Filament\Admin\Resources\BankAccounts\Schemas\BankAccountInfolist;
use App\Filament\Admin\Resources\BankAccounts\Tables\BankAccountsTable;
use App\Models\BankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = BankAccount::name;

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
            //
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

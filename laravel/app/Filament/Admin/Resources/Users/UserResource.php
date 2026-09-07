<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Schemas\UserInfolist;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    // Ungruppiert, vor dem Horizon-Navigationseintrag (sort 10) im AdminPanelProvider.
    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = User::name;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Verhindert, dass ein Admin sich selbst löscht - sowohl über die
     * Einzel- als auch über die Bulk-Delete-Action.
     */
    public static function canDelete(Model $record): bool
    {
        return $record->isNot(Auth::user());
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.user.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.user.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.user.plural_model_label');
    }
}

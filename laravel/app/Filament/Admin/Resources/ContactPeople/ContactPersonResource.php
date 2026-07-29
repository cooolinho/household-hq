<?php

namespace App\Filament\Admin\Resources\ContactPeople;

use App\Filament\Admin\Resources\ContactPeople\Pages\CreateContactPerson;
use App\Filament\Admin\Resources\ContactPeople\Pages\EditContactPerson;
use App\Filament\Admin\Resources\ContactPeople\Pages\ListContactPeople;
use App\Filament\Admin\Resources\ContactPeople\Pages\ViewContactPerson;
use App\Filament\Admin\Resources\ContactPeople\Schemas\ContactPersonForm;
use App\Filament\Admin\Resources\ContactPeople\Schemas\ContactPersonInfolist;
use App\Filament\Admin\Resources\ContactPeople\Tables\ContactPeopleTable;
use App\Models\ContactPerson;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactPersonResource extends Resource
{
    protected static ?string $model = ContactPerson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static string|null|\UnitEnum $navigationGroup = 'Basis';
    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = ContactPerson::firstname;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.contact_person.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.contact_person.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.contact_person.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(ContactPerson::user_id, auth()->id())->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return ContactPersonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactPersonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactPeopleTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(ContactPerson::user_id, auth()->id());
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
            'index' => ListContactPeople::route('/'),
            'create' => CreateContactPerson::route('/create'),
            'view' => ViewContactPerson::route('/{record}'),
            'edit' => EditContactPerson::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof ContactPerson) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

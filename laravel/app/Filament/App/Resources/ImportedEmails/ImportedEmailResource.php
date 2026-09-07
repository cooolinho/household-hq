<?php

namespace App\Filament\App\Resources\ImportedEmails;

use App\Filament\App\Resources\ImportedEmails\Pages\ListImportedEmails;
use App\Filament\App\Resources\ImportedEmails\Pages\ViewImportedEmail;
use App\Filament\App\Resources\ImportedEmails\Schemas\ImportedEmailInfolist;
use App\Filament\App\Resources\ImportedEmails\Tables\ImportedEmailsTable;
use App\Menu\NavigationGroup;
use App\Models\ImportedEmail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ImportedEmailResource extends Resource
{
    protected static ?string $model = ImportedEmail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::FEATURES;

    protected static ?int $navigationSort = 95;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.imported_email.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.imported_email.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.imported_email.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ImportedEmail::query()
            ->where(ImportedEmail::user_id, auth()->id())
            ->where(ImportedEmail::warning_count, '>', 0)
            ->count();

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImportedEmailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportedEmailsTable::configure($table)
            ->modifyQueryUsing(fn($query) => $query
                ->where(ImportedEmail::user_id, auth()->id())
                ->with([ImportedEmail::belongs_to_imap_account])
                ->withCount([ImportedEmail::has_many_attachments])
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportedEmails::route('/'),
            'view' => ViewImportedEmail::route('/{record}'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof ImportedEmail && $record->{ImportedEmail::user_id} === auth()->id();
    }
}


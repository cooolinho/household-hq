<?php

namespace App\Filament\Admin\Resources\ApplicationLogs;

use App\Filament\Admin\Resources\ApplicationLogs\Pages\ListApplicationLogs;
use App\Filament\Admin\Resources\ApplicationLogs\Pages\ViewApplicationLog;
use App\Filament\Admin\Resources\ApplicationLogs\Schemas\ApplicationLogInfolist;
use App\Filament\Admin\Resources\ApplicationLogs\Tables\ApplicationLogsTable;
use App\Menu\NavigationGroup;
use App\Models\ApplicationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApplicationLogResource extends Resource
{
    protected static ?string $model = ApplicationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::FEATURES;

    protected static ?int $navigationSort = 96;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.application_log.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.application_log.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.application_log.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApplicationLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApplicationLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplicationLogs::route('/'),
            'view' => ViewApplicationLog::route('/{record}'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}



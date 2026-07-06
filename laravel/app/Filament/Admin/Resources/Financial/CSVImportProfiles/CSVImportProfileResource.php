<?php

namespace App\Filament\Admin\Resources\Financial\CSVImportProfiles;

use App\Filament\Admin\Resources\Financial\CSVImportProfiles\Pages\CreateCSVImportProfile;
use App\Filament\Admin\Resources\Financial\CSVImportProfiles\Pages\EditCSVImportProfile;
use App\Filament\Admin\Resources\Financial\CSVImportProfiles\Pages\ListCSVImportProfiles;
use App\Filament\Admin\Resources\Financial\CSVImportProfiles\Schemas\CSVImportProfileForm;
use App\Filament\Admin\Resources\Financial\CSVImportProfiles\Tables\CSVImportProfilesTable;
use App\Models\Financial\CSVImportProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CSVImportProfileResource extends Resource
{
    protected static ?string $model = CSVImportProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|null|\UnitEnum $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return CSVImportProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CSVImportProfilesTable::configure($table);
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
            'index' => ListCSVImportProfiles::route('/'),
            'create' => CreateCSVImportProfile::route('/create'),
            'edit' => EditCSVImportProfile::route('/{record}/edit'),
        ];
    }
}

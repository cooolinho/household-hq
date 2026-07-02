<?php

namespace App\Filament\Admin\Resources\Insurances;

use App\Filament\Admin\Resources\Insurances\Pages\CreateInsurance;
use App\Filament\Admin\Resources\Insurances\Pages\EditInsurance;
use App\Filament\Admin\Resources\Insurances\Pages\ListInsurances;
use App\Filament\Admin\Resources\Insurances\Pages\ViewInsurance;
use App\Filament\Admin\Resources\Insurances\RelationManagers\DocumentsRelationManager;
use App\Filament\Admin\Resources\Insurances\Schemas\InsuranceForm;
use App\Filament\Admin\Resources\Insurances\Schemas\InsuranceInfolist;
use App\Filament\Admin\Resources\Insurances\Tables\InsurancesTable;
use App\Models\Insurance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InsuranceResource extends Resource
{
    protected static ?string $model = Insurance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = Insurance::name;

    public static function form(Schema $schema): Schema
    {
        return InsuranceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InsuranceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsurancesTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(Insurance::user_id, auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsurances::route('/'),
            'create' => CreateInsurance::route('/create'),
            'view' => ViewInsurance::route('/{record}'),
            'edit' => EditInsurance::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Insurance) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

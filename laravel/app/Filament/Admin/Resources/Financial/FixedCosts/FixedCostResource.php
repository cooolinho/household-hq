<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts;

use App\Filament\Admin\Resources\Financial\FixedCosts\Pages\CreateFixedCost;
use App\Filament\Admin\Resources\Financial\FixedCosts\Pages\EditFixedCost;
use App\Filament\Admin\Resources\Financial\FixedCosts\Pages\ListFixedCosts;
use App\Filament\Admin\Resources\Financial\FixedCosts\Pages\ViewFixedCost;
use App\Filament\Admin\Resources\Financial\FixedCosts\RelationManagers\DocumentsRelationManager;
use App\Filament\Admin\Resources\Financial\FixedCosts\RelationManagers\TransactionsRelationManager;
use App\Filament\Admin\Resources\Financial\FixedCosts\Schemas\FixedCostForm;
use App\Filament\Admin\Resources\Financial\FixedCosts\Schemas\FixedCostInfolist;
use App\Filament\Admin\Resources\Financial\FixedCosts\Tables\FixedCostsTable;
use App\Models\Financial\FixedCost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FixedCostResource extends Resource
{
    const string PAGE_VIEW = 'view';
    protected static ?string $model = FixedCost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Fixkosten';
    protected static string|null|\UnitEnum $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return FixedCostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FixedCostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FixedCostsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(FixedCost::user_id, auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFixedCosts::route('/'),
            'create' => CreateFixedCost::route('/create'),
            self::PAGE_VIEW => ViewFixedCost::route('/{record}'),
            'edit' => EditFixedCost::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof FixedCost) {
            return $record->user_id === auth()->id();
        }

        return false;
    }

    public static function getViewUrl(int $recordId): string
    {
        return self::getUrl(self::PAGE_VIEW, ['record' => $recordId]);
    }
}

<?php

namespace App\Filament\App\Resources\Financial\Goals;

use App\Filament\App\Resources\Financial\Goals\Pages\CreateGoal;
use App\Filament\App\Resources\Financial\Goals\Pages\EditGoal;
use App\Filament\App\Resources\Financial\Goals\Pages\ListGoals;
use App\Filament\App\Resources\Financial\Goals\Pages\ViewGoal;
use App\Filament\App\Resources\Financial\Goals\RelationManagers\ContributionsRelationManager;
use App\Filament\App\Resources\Financial\Goals\RelationManagers\TransactionsRelationManager;
use App\Filament\App\Resources\Financial\Goals\Schemas\GoalForm;
use App\Filament\App\Resources\Financial\Goals\Schemas\GoalInfolist;
use App\Menu\NavigationGroup;
use App\Models\Financial\Goal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;
    protected static string|null|UnitEnum $navigationGroup = NavigationGroup::BANKS;
    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = Goal::name;

    public static function getNavigationLabel(): string
    {
        return 'Ziele';
    }

    public static function getModelLabel(): string
    {
        return 'Ziel';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ziele';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Goal::query()
            ->activeForUser((int)auth()->id())
            ->count();

        return $count > 0 ? (string)$count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return GoalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GoalInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ContributionsRelationManager::class,
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoals::route('/'),
            'create' => CreateGoal::route('/create'),
            'view' => ViewGoal::route('/{record}'),
            'edit' => EditGoal::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Goal) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

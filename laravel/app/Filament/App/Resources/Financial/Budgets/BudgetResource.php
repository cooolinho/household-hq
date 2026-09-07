<?php

namespace App\Filament\App\Resources\Financial\Budgets;

use App\Filament\App\Resources\Financial\Budgets\Pages\CreateBudget;
use App\Filament\App\Resources\Financial\Budgets\Pages\EditBudget;
use App\Filament\App\Resources\Financial\Budgets\Pages\ListBudgets;
use App\Filament\App\Resources\Financial\Budgets\Pages\ViewBudget;
use App\Filament\App\Resources\Financial\Budgets\RelationManagers\TransactionsRelationManager;
use App\Filament\App\Resources\Financial\Budgets\Schemas\BudgetForm;
use App\Filament\App\Resources\Financial\Budgets\Schemas\BudgetInfolist;
use App\Menu\NavigationGroup;
use App\Models\Enums\BudgetStatusEnum;
use App\Models\Financial\Budget;
use App\Services\Budget\BudgetCalculation;
use App\Services\Budget\BudgetCalculationService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;
    protected static string|null|UnitEnum $navigationGroup = NavigationGroup::BANKS;
    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = Budget::name;

    /**
     * Pro Request gecachte Auswertung – getNavigationBadge() und
     * getNavigationBadgeColor() werden auf jeder Panel-Seite aufgerufen.
     *
     * @var list<BudgetCalculation>|null
     */
    private static ?array $navigationCalculations = null;

    public static function getNavigationLabel(): string
    {
        return 'Budgets';
    }

    public static function getModelLabel(): string
    {
        return 'Budget';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Budgets';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = count(self::navigationCalculations());

        return $count > 0 ? (string)$count : null;
    }

    /**
     * @return list<BudgetCalculation>
     */
    private static function navigationCalculations(): array
    {
        return self::$navigationCalculations ??= app(BudgetCalculationService::class)
            ->calculateForUser((int)auth()->id());
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $worst = array_reduce(
            self::navigationCalculations(),
            static fn(BudgetStatusEnum $carry, BudgetCalculation $calculation): BudgetStatusEnum => $calculation->status->severity() > $carry->severity()
                ? $calculation->status
                : $carry,
            BudgetStatusEnum::OK,
        );

        return $worst === BudgetStatusEnum::OK ? 'primary' : $worst->color();
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BudgetInfolist::configure($schema);
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
            'index' => ListBudgets::route('/'),
            'create' => CreateBudget::route('/create'),
            'view' => ViewBudget::route('/{record}'),
            'edit' => EditBudget::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Budget) {
            return $record->user_id === auth()->id();
        }

        return false;
    }
}

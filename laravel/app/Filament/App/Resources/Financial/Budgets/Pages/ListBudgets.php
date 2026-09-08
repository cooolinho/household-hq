<?php

namespace App\Filament\App\Resources\Financial\Budgets\Pages;

use App\Filament\App\Resources\Financial\Budgets\BudgetResource;
use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostBalanceWidget;
use App\Models\Financial\Budget;
use App\Services\Budget\BudgetCalculation;
use App\Services\Budget\BudgetCalculationService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;

/**
 * Übersicht aller Budgets als Karten-Grid mit Ampelindikator statt einer Tabelle.
 */
class ListBudgets extends Page
{
    protected static string $resource = BudgetResource::class;

    protected string $view = 'filament.app.resources.financial.budgets.pages.list-budgets';

    public function getTitle(): string
    {
        return 'Budgets';
    }

    /**
     * @return list<BudgetCalculation>
     */
    public function budgetCalculations(): array
    {
        return app(BudgetCalculationService::class)->calculateForUser((int)auth()->id());
    }

    public function hasInactiveBudgets(): bool
    {
        return Budget::query()
            ->where(Budget::user_id, auth()->id())
            ->where(Budget::active, false)
            ->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Budget anlegen'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FixedCostBalanceWidget::make([
                'heading' => 'Bilanz zum Monatsende',
            ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}

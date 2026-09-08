<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Widgets;

use App\Models\DashboardWidgetPreference;
use App\Services\FixedCost\FixedCostBalanceService;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

/**
 * Monats-/Jahresbilanz aus Fixkosten, optional inkl. der dafür markierten Budgets.
 * Der Schalter persistiert je Benutzer und synchronisiert Geschwister-Widgets über ein Livewire-Event.
 */
class FixedCostBalanceWidget extends Widget
{
    public const string BUDGETS_TOGGLED = 'fixed-cost-balance-budgets-toggled';

    public int|string|array $columnSpan = 'full';

    public bool $includeBudgets = true;

    public string $heading = 'Fixkosten-Bilanz';

    protected string $view = 'filament.app.resources.financial.fixed-costs.widgets.fixed-cost-balance-widget';

    public function mount(): void
    {
        $this->includeBudgets = (bool)$this->preference()
            ->{DashboardWidgetPreference::include_budgets_in_balance};
    }

    public function updatedIncludeBudgets(bool $state): void
    {
        $this->preference()->update([
            DashboardWidgetPreference::include_budgets_in_balance => $state,
        ]);

        $this->dispatch(self::BUDGETS_TOGGLED, includeBudgets: $state);
    }

    #[On(self::BUDGETS_TOGGLED)]
    public function syncIncludeBudgets(bool $includeBudgets): void
    {
        // Guard gegen die Rücklieferung des selbst gesendeten Events.
        if ($this->includeBudgets === $includeBudgets) {
            return;
        }

        $this->includeBudgets = $includeBudgets;
    }

    protected function getViewData(): array
    {
        $balance = app(FixedCostBalanceService::class)
            ->balanceForUser((int)auth()->id(), $this->includeBudgets);

        return [
            'balance' => $balance,
            'heading' => $this->heading,
        ];
    }

    private function preference(): DashboardWidgetPreference
    {
        return DashboardWidgetPreference::forUser((int)auth()->id());
    }
}

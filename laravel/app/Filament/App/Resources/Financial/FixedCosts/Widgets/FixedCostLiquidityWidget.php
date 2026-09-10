<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Widgets;

use App\Models\DashboardWidgetPreference;
use App\Services\FixedCost\FixedCostBalanceService;
use App\Services\FixedCost\FixedCostPeriodResolver;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

/**
 * Wie viel Geld noch auf dem Konto liegen muss, um alle bis zum Periodenende noch
 * ausstehenden Fixkosten sicher zu begleichen. Die Periode ist der Kalendermonat, sofern der
 * Benutzer keinen abweichenden Periodenstart-Tag hinterlegt hat (siehe DashboardSettingsPage).
 */
class FixedCostLiquidityWidget extends Widget
{
    public int|string|array $columnSpan = 'full';

    public bool $includeBudgets = true;

    protected string $view = 'filament.app.resources.financial.fixed-costs.widgets.fixed-cost-liquidity-widget';

    public function mount(): void
    {
        $this->includeBudgets = (bool)$this->preference()
            ->{DashboardWidgetPreference::include_budgets_in_balance};
    }

    private function preference(): DashboardWidgetPreference
    {
        return DashboardWidgetPreference::forUser((int)auth()->id());
    }

    public function updatedIncludeBudgets(bool $state): void
    {
        $this->preference()->update([
            DashboardWidgetPreference::include_budgets_in_balance => $state,
        ]);

        $this->dispatch(FixedCostBalanceWidget::BUDGETS_TOGGLED, includeBudgets: $state);
    }

    #[On(FixedCostBalanceWidget::BUDGETS_TOGGLED)]
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
        $preference = $this->preference();
        $today = CarbonImmutable::today();

        $period = app(FixedCostPeriodResolver::class)->resolve(
            (int)$preference->{DashboardWidgetPreference::period_start_day},
            $today,
        );

        $requirement = app(FixedCostBalanceService::class)->liquidityRequirement(
            (int)auth()->id(),
            $today,
            $period['end'],
            $this->includeBudgets,
            (string)$preference->{DashboardWidgetPreference::currency},
        );

        return ['requirement' => $requirement];
    }
}

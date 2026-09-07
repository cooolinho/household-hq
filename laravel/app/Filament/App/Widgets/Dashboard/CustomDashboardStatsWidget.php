<?php

namespace App\Filament\App\Widgets\Dashboard;

use App\Filament\App\Widgets\Dashboard\Concerns\LoadsCustomDashboardUserWidget;
use App\Models\CustomDashboardUserWidget;
use App\Services\Dashboard\Widgets\Types\StatWidgetService;
use Filament\Widgets\StatsOverviewWidget;

class CustomDashboardStatsWidget extends StatsOverviewWidget
{
    use LoadsCustomDashboardUserWidget;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $widget = $this->getCustomDashboardWidget();

        if ($widget === null) {
            return [];
        }

        return app(StatWidgetService::class)->getStats($widget);
    }

    protected function getHeading(): ?string
    {
        return $this->getCustomDashboardWidget()?->{CustomDashboardUserWidget::title};
    }
}

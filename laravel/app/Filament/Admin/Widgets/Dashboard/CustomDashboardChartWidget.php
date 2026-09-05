<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Filament\Admin\Widgets\Dashboard\Concerns\LoadsCustomDashboardUserWidget;
use App\Models\CustomDashboardUserWidget;
use App\Services\Dashboard\Widgets\Types\ChartWidgetService;
use Filament\Widgets\ChartWidget;

class CustomDashboardChartWidget extends ChartWidget
{
    use LoadsCustomDashboardUserWidget;

    protected static bool $isDiscovered = false;

    public function getHeading(): ?string
    {
        return $this->getCustomDashboardWidget()?->{CustomDashboardUserWidget::title};
    }

    protected function getType(): string
    {
        $widget = $this->getCustomDashboardWidget();

        return $widget === null
            ? 'line'
            : app(ChartWidgetService::class)->getType($widget);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $widget = $this->getCustomDashboardWidget();

        if ($widget === null) {
            return ['labels' => [], 'datasets' => []];
        }

        return app(ChartWidgetService::class)->getData($widget);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        $widget = $this->getCustomDashboardWidget();

        return $widget === null
            ? []
            : app(ChartWidgetService::class)->getOptions($widget);
    }
}

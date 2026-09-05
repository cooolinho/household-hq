<?php

namespace App\Filament\Admin\Widgets\Dashboard\Concerns;

use App\Models\CustomDashboardUserWidget;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplateRegistry;

trait LoadsCustomDashboardUserWidget
{
    public int $customWidgetId = 0;

    protected ?CustomDashboardUserWidget $customDashboardWidget = null;

    protected bool $customDashboardWidgetLoaded = false;

    public function getColumnSpan(): int|string|array
    {
        $width = (string)($this->getCustomDashboardWidget()?->{CustomDashboardUserWidget::width} ?? CustomDashboardUserWidget::WIDTH_FULL);

        return match ($width) {
            CustomDashboardUserWidget::WIDTH_SMALL => 1,
            CustomDashboardUserWidget::WIDTH_HALF => 2,
            CustomDashboardUserWidget::WIDTH_FULL_GRID => ['md' => 2, 'xl' => 4],
            default => CustomDashboardUserWidget::WIDTH_FULL,
        };
    }

    protected function getCustomDashboardWidgetTemplate(): ?CustomDashboardWidgetTemplate
    {
        $widget = $this->getCustomDashboardWidget();

        if ($widget === null) {
            return null;
        }

        return app(CustomDashboardWidgetTemplateRegistry::class)->templateForWidget($widget);
    }

    protected function getCustomDashboardWidget(): ?CustomDashboardUserWidget
    {
        if ($this->customDashboardWidgetLoaded) {
            return $this->customDashboardWidget;
        }

        $this->customDashboardWidgetLoaded = true;
        $userId = auth()->id();

        if (!$userId || $this->customWidgetId < 1) {
            return null;
        }

        return $this->customDashboardWidget = CustomDashboardUserWidget::query()
            ->where(CustomDashboardUserWidget::id, $this->customWidgetId)
            ->where(CustomDashboardUserWidget::user_id, $userId)
            ->where(CustomDashboardUserWidget::is_active, true)
            ->first();
    }
}

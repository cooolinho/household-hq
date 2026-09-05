<?php

namespace App\Services\Dashboard\Widgets\Types;

use App\Models\CustomDashboardUserWidget;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplateRegistry;

final class ChartWidgetService
{
    public function __construct(
        private readonly CustomDashboardWidgetTemplateRegistry $registry,
    )
    {
    }

    public function getType(CustomDashboardUserWidget $widget): string
    {
        return $this->registry->templateForWidget($widget)?->chartType() ?? 'line';
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(CustomDashboardUserWidget $widget): array
    {
        $template = $this->registry->templateForWidget($widget);

        if ($template === null || $template->type !== CustomDashboardWidgetTypeEnum::CHART) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $data = $template->resolveData(
            (int)$widget->{CustomDashboardUserWidget::user_id},
            (array)$widget->{CustomDashboardUserWidget::configuration},
        );

        return [
            'labels' => is_array($data['labels'] ?? null) ? $data['labels'] : [],
            'datasets' => is_array($data['datasets'] ?? null) ? $data['datasets'] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(CustomDashboardUserWidget $widget): array
    {
        return [];
    }
}

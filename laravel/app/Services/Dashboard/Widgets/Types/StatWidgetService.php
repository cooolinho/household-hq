<?php

namespace App\Services\Dashboard\Widgets\Types;

use App\Models\CustomDashboardUserWidget;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplateRegistry;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class StatWidgetService
{
    public function __construct(
        private readonly CustomDashboardWidgetTemplateRegistry $registry,
    )
    {
    }

    /**
     * @return array<int, Stat>
     */
    public function getStats(CustomDashboardUserWidget $widget): array
    {
        $template = $this->registry->templateForWidget($widget);

        if ($template === null || $template->type !== CustomDashboardWidgetTypeEnum::STAT) {
            return [];
        }

        $data = $template->resolveData(
            (int)$widget->{CustomDashboardUserWidget::user_id},
            (array)$widget->{CustomDashboardUserWidget::configuration},
        );
        $stats = [];

        foreach (array_values($data) as $item) {
            if (!is_array($item) || !isset($item['label'], $item['value'])) {
                continue;
            }

            $stat = Stat::make((string)$item['label'], (string)$item['value']);

            if (isset($item['description'])) {
                $stat->description((string)$item['description']);
            }

            if (isset($item['color'])) {
                $stat->color((string)$item['color']);
            }

            if (isset($item['icon'])) {
                $stat->icon($item['icon']);
            }

            $stats[] = $stat;
        }

        return $stats;
    }
}

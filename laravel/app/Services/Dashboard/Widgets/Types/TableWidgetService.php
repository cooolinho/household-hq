<?php

namespace App\Services\Dashboard\Widgets\Types;

use App\Models\CustomDashboardUserWidget;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplateRegistry;
use Filament\Tables\Table;

final class TableWidgetService
{
    public function __construct(
        private readonly CustomDashboardWidgetTemplateRegistry $registry,
    )
    {
    }

    public function configure(CustomDashboardUserWidget $widget, Table $table): Table
    {
        $template = $this->registry->templateForWidget($widget);

        if ($template === null || $template->type !== CustomDashboardWidgetTypeEnum::TABLE) {
            return $table
                ->heading((string)$widget->{CustomDashboardUserWidget::title})
                ->query(CustomDashboardUserWidget::query()->whereKey(0))
                ->paginated(false)
                ->columns([]);
        }

        return $template
            ->configureTable(
                $table,
                (int)$widget->{CustomDashboardUserWidget::user_id},
                (array)$widget->{CustomDashboardUserWidget::configuration},
            )
            ->heading((string)$widget->{CustomDashboardUserWidget::title});
    }
}

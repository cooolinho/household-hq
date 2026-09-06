<?php

namespace App\Filament\Admin\Resources\Financial\Goals\Pages;

use App\Filament\Admin\Resources\Financial\Goals\GoalResource;
use App\Filament\Admin\Resources\Financial\Goals\Widgets\GoalProgressChartWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGoal extends ViewRecord
{
    protected static string $resource = GoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            GoalProgressChartWidget::class,
        ];
    }
}

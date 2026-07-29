<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\Widgets\FixedCostsDashboardWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getWidgets(): array
    {
        return [
            FixedCostsDashboardWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}


<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\Widgets\FixedCostsDashboardWidget;
use App\Filament\Admin\Resources\Financial\Insurances\Widgets\InsuranceDashboardWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getWidgets(): array
    {
        return [
            FixedCostsDashboardWidget::class,
            InsuranceDashboardWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}


<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Pages;

use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use App\Filament\Admin\Resources\Financial\Insurances\Widgets\InsuranceDashboardWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsurances extends ListRecords
{
    protected static string $resource = InsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InsuranceDashboardWidget::class,
        ];
    }
}

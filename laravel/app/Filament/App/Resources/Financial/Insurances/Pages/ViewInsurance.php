<?php

namespace App\Filament\App\Resources\Financial\Insurances\Pages;

use App\Filament\App\Resources\Documents\Actions\AssignExistingDocumentAction;
use App\Filament\App\Resources\Financial\Insurances\InsuranceResource;
use App\Filament\App\Widgets\CommentsWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInsurance extends ViewRecord
{
    protected static string $resource = InsuranceResource::class;

    protected string $view = 'filament.app.resources.financial.insurances.pages.view-insurance';

    protected function getHeaderActions(): array
    {
        return [
            AssignExistingDocumentAction::make(),
            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CommentsWidget::class,
        ];
    }
}

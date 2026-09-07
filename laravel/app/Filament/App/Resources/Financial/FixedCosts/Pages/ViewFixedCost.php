<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Pages;

use App\Filament\App\Resources\Documents\Actions\AssignExistingDocumentAction;
use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\App\Widgets\CommentsWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedCost extends ViewRecord
{
    protected static string $resource = FixedCostResource::class;

    protected string $view = 'filament.app.resources.financial.fixed-costs.pages.view-fixed-cost';

    protected function getHeaderActions(): array
    {
        return [
            AssignExistingDocumentAction::make(),
            EditAction::make(),
        ];
    }

//    public function getRelationManagers(): array
//    {
//        return [];
//    }

    protected function getFooterWidgets(): array
    {
        return [
            CommentsWidget::class,
        ];
    }
}

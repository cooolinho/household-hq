<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Pages;

use App\Filament\Admin\Resources\Documents\Actions\AssignExistingDocumentAction;
use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\Admin\Widgets\CommentsWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedCost extends ViewRecord
{
    protected static string $resource = FixedCostResource::class;

    protected string $view = 'filament.admin.resources.financial.fixed-costs.pages.view-fixed-cost';

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

<?php

namespace App\Filament\App\Resources\Financial\Insurances\Pages;

use App\Filament\App\Pages\Features\MoveNotificationWizard;
use App\Filament\App\Resources\Financial\Insurances\InsuranceResource;
use App\Filament\App\Resources\Financial\Insurances\Widgets\InsuranceDashboardWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsurances extends ListRecords
{
    protected static string $resource = InsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('move_notification_wizard')
                ->label('Umzug mitteilen')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->url(fn(): string => MoveNotificationWizard::getUrl()),
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

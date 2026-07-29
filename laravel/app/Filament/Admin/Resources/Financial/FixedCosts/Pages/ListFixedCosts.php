<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Jobs\SendUpcomingFixedCostsReminderJob;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListFixedCosts extends ListRecords
{
    protected static string $resource = FixedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                Action::make('send_fixed_costs_reminder_now')
                    ->label('Erinnerungsmail jetzt senden')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        SendUpcomingFixedCostsReminderJob::dispatchAfterResponse();

                        Notification::make()
                            ->success()
                            ->title('Erinnerungsmail wurde versendet.')
                            ->send();
                    }),
            ])
            ->button(),
        ];
    }
}

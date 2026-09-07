<?php

namespace App\Filament\App\Resources\Reminders\Pages;

use App\Filament\App\Resources\Reminders\ReminderResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReminders extends ListRecords
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                ReminderResource::getSendRemindersNowAction(),
            ])->button(),
        ];
    }
}

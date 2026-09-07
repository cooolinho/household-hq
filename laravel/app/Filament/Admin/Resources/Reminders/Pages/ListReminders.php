<?php

namespace App\Filament\Admin\Resources\Reminders\Pages;

use App\Filament\Admin\Resources\Reminders\ReminderResource;
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

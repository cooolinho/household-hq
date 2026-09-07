<?php

namespace App\Filament\App\Resources\Reminders\Pages;

use App\Filament\App\Resources\Reminders\ReminderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReminder extends ViewRecord
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Reminders\Pages;

use App\Filament\Admin\Resources\Reminders\ReminderResource;
use App\Filament\Admin\Resources\Reminders\Schemas\ReminderForm;
use App\Models\Reminder;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReminder extends EditRecord
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data[ReminderForm::FIELD_TARGET_MODE] ?? null) === ReminderForm::TARGET_MODE_FREE) {
            $data[Reminder::remindable_type] = null;
            $data[Reminder::remindable_id] = null;
            $data[Reminder::date_property] = null;
        } else {
            $data[Reminder::message] = null;
        }

        return $data;
    }
}

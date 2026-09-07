<?php

namespace App\Filament\Admin\Resources\Reminders\Pages;

use App\Filament\Admin\Resources\Reminders\ReminderResource;
use App\Filament\Admin\Resources\Reminders\Schemas\ReminderForm;
use App\Models\Reminder;
use Filament\Resources\Pages\CreateRecord;

class CreateReminder extends CreateRecord
{
    protected static string $resource = ReminderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Reminder::user_id] = auth()->id();

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

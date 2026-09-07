<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ReminderSettings extends Settings
{
    public bool $enabled = true;
    public string $default_run_at_time = '08:00';
    public int $catch_up_hours = 48;

    public static function group(): string
    {
        return 'reminders';
    }
}

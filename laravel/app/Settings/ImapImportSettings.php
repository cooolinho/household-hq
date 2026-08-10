<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ImapImportSettings extends Settings
{
    public bool $enabled;
    public int $schedule_minutes;

    public static function group(): string
    {
        return 'imap_import';
    }
}


<?php

namespace App\Filament\Admin\Resources\ApplicationLogs\Pages;

use App\Filament\Admin\Resources\ApplicationLogs\ApplicationLogResource;
use Filament\Resources\Pages\ListRecords;

class ListApplicationLogs extends ListRecords
{
    protected static string $resource = ApplicationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}


<?php

namespace App\Filament\Admin\Resources\ImportedEmails\Pages;

use App\Filament\Admin\Resources\ImportedEmails\ImportedEmailResource;
use Filament\Resources\Pages\ListRecords;

class ListImportedEmails extends ListRecords
{
    protected static string $resource = ImportedEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}


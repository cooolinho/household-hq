<?php

namespace App\Filament\App\Resources\ImportedEmails\Pages;

use App\Filament\App\Resources\ImportedEmails\ImportedEmailResource;
use Filament\Resources\Pages\ListRecords;

class ListImportedEmails extends ListRecords
{
    protected static string $resource = ImportedEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}


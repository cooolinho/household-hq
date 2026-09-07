<?php

namespace App\Filament\App\Resources\ImportedEmails\Pages;

use App\Filament\App\Resources\ImportedEmails\ImportedEmailResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewImportedEmail extends ViewRecord
{
    protected static string $resource = ImportedEmailResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('admin.resource.imported_email.model_label');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}


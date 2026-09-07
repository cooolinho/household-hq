<?php

namespace App\Filament\App\Resources\Financial\CSVImportProfiles\Pages;

use App\Filament\App\Resources\Financial\CSVImportProfiles\CSVImportProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCSVImportProfiles extends ListRecords
{
    protected static string $resource = CSVImportProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

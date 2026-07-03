<?php

namespace App\Filament\Admin\Resources\CSVImportProfiles\Pages;

use App\Filament\Admin\Resources\CSVImportProfiles\CSVImportProfileResource;
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

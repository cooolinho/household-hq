<?php

namespace App\Filament\App\Resources\Financial\CSVImportProfiles\Pages;

use App\Filament\App\Resources\Financial\CSVImportProfiles\CSVImportProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCSVImportProfile extends EditRecord
{
    protected static string $resource = CSVImportProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

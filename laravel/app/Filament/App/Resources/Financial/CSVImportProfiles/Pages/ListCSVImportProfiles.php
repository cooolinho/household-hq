<?php

namespace App\Filament\App\Resources\Financial\CSVImportProfiles\Pages;

use App\Filament\App\Pages\TransactionCsvImportPage;
use App\Filament\App\Resources\Financial\CSVImportProfiles\CSVImportProfileResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCSVImportProfiles extends ListRecords
{
    protected static string $resource = CSVImportProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('CSV importieren')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->url(TransactionCsvImportPage::getUrl()),
            CreateAction::make(),
        ];
    }
}

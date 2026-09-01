<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Pages;

use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransactionCategories extends ListRecords
{
    protected static string $resource = TransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

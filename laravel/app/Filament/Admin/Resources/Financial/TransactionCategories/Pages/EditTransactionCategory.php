<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Pages;

use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransactionCategory extends EditRecord
{
    protected static string $resource = TransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

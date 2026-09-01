<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Pages;

use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransactionCategory extends CreateRecord
{
    protected static string $resource = TransactionCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}

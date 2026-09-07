<?php

namespace App\Filament\App\Resources\Financial\Budgets\Pages;

use App\Filament\App\Resources\Financial\Budgets\BudgetResource;
use App\Models\Financial\Budget;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Budget::user_id] = auth()->id();

        return $data;
    }
}

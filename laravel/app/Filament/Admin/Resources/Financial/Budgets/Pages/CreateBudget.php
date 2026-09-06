<?php

namespace App\Filament\Admin\Resources\Financial\Budgets\Pages;

use App\Filament\Admin\Resources\Financial\Budgets\BudgetResource;
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

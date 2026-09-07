<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Pages;

use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\App\Resources\Financial\FixedCosts\Schemas\FixedCostForm;
use App\Models\Financial\FixedCost;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFixedCost extends EditRecord
{
    public Model|FixedCost|int|string|null $record;

    protected static string $resource = FixedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    // fill component values with the current record's values
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data[FixedCostForm::INPUT_ASSIGN_WITH_INSURANCE] = $this->record->insurance_id !== null;
        return $data;
    }
}

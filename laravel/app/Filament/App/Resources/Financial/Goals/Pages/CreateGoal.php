<?php

namespace App\Filament\App\Resources\Financial\Goals\Pages;

use App\Filament\App\Resources\Financial\Goals\GoalResource;
use App\Models\Financial\Goal;
use Filament\Resources\Pages\CreateRecord;

class CreateGoal extends CreateRecord
{
    protected static string $resource = GoalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Goal::user_id] = auth()->id();

        return $data;
    }
}

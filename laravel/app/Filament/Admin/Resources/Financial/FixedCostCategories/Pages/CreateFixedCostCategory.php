<?php

namespace App\Filament\Admin\Resources\Financial\FixedCostCategories\Pages;

use App\Filament\Admin\Resources\Financial\FixedCostCategories\FixedCostCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedCostCategory extends CreateRecord
{
    protected static string $resource = FixedCostCategoryResource::class;
}


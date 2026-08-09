<?php

namespace App\Filament\Admin\Resources\Financial\FixedCostCategories\RelationManagers;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Models\Financial\FixedCostCategory;
use Filament\Resources\RelationManagers\RelationManager;

class FixedCostsRelationManager extends RelationManager
{
    protected static string $relationship = FixedCostCategory::has_many_fixed_costs;
    protected static ?string $relatedResource = FixedCostResource::class;
}

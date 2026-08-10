<?php

namespace App\Filament\Admin\Resources\Financial\InsuranceCategories\RelationManagers;

use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use App\Models\Financial\InsuranceCategory;
use Filament\Resources\RelationManagers\RelationManager;

class InsurancesRelationManager extends RelationManager
{
    protected static string $relationship = InsuranceCategory::has_many_insurances;
    protected static ?string $relatedResource = InsuranceResource::class;
}


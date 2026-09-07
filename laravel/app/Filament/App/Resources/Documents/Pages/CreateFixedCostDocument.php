<?php

namespace App\Filament\App\Resources\Documents\Pages;

use App\Filament\App\Resources\Documents\Schemas\FixedCostDocumentForm;
use App\Models\Financial\FixedCost;

class CreateFixedCostDocument extends CreateRelatedDocument
{
    protected function getOwnerModelClass(): string
    {
        return FixedCost::class;
    }

    protected function getDocumentFormClass(): string
    {
        return FixedCostDocumentForm::class;
    }
}


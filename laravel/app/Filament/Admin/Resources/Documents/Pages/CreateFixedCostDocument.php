<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\Schemas\FixedCostDocumentForm;
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


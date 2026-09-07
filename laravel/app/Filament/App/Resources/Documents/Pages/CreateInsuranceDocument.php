<?php

namespace App\Filament\App\Resources\Documents\Pages;

use App\Filament\App\Resources\Documents\Schemas\InsuranceDocumentForm;
use App\Models\Financial\Insurance;

class CreateInsuranceDocument extends CreateRelatedDocument
{
    protected function getOwnerModelClass(): string
    {
        return Insurance::class;
    }

    protected function getDocumentFormClass(): string
    {
        return InsuranceDocumentForm::class;
    }
}


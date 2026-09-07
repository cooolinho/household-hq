<?php

namespace App\Filament\App\Resources\Financial\Insurances\RelationManagers;

use App\Filament\App\Resources\Documents\DocumentResource;
use App\Filament\App\Resources\Documents\Tables\DocumentsTable;
use App\Models\Financial\Insurance;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    const string QUERY_PARAM_INSURANCE_ID = 'insurance_id';
    protected static string $relationship = Insurance::has_many_documents;

    protected static ?string $relatedResource = DocumentResource::class;

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->url(fn(): string => DocumentResource::getUrl(DocumentResource::PAGE_CREATE_FOR_INSURANCE, parameters: [
                        'owner' => $this->ownerRecord->getKey(),
                    ])),
            ]);
    }
}

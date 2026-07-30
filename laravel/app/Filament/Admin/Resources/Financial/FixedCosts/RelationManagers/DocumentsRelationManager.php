<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\RelationManagers;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Financial\FixedCost;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = FixedCost::has_many_documents;

    protected static ?string $relatedResource = DocumentResource::class;

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->url(fn(): string => DocumentResource::getUrl(DocumentResource::PAGE_CREATE_FOR_FIXED_COST, parameters: [
                        'owner' => $this->ownerRecord->getKey(),
                    ])),
            ]);
    }
}


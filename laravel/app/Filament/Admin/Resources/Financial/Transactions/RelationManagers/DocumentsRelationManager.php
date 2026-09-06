<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\RelationManagers;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Financial\Transaction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = Transaction::has_many_documents;

    protected static ?string $relatedResource = DocumentResource::class;

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }
}

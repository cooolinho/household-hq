<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\RelationManagers;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = MeasurementDeviceContract::has_many_documents;

    protected static ?string $relatedResource = DocumentResource::class;

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->url(fn(): string => DocumentResource::getUrl(
                        DocumentResource::PAGE_CREATE_FOR_MEASUREMENT_DEVICE_CONTRACT,
                        parameters: ['owner' => $this->ownerRecord->getKey()],
                    )),
                AttachAction::make()
                    ->label('Vorhandenes Dokument zuweisen')
                    ->recordSelectOptionsQuery(fn(Builder $query) => $query->where(Document::user_id, auth()->id())),
            ]);
    }
}

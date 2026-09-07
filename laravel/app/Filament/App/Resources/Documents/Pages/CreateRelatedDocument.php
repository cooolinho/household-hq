<?php

namespace App\Filament\App\Resources\Documents\Pages;

use App\Filament\App\Resources\Documents\DocumentResource;
use App\Filament\App\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Models\Document;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

abstract class CreateRelatedDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    #[Locked]
    public string $ownerModelClass;

    #[Locked]
    public int|string $owner;

    public function mount(): void
    {
        $owner = request()->route('owner');

        abort_unless(filled($owner), 404);

        $this->ownerModelClass = $this->getOwnerModelClass();
        $this->owner = $owner;

        $ownerRecord = $this->getOwnerRecord();

        abort_unless($ownerRecord instanceof Model, 404);
        abort_unless(DocumentOwnerRegistry::canAccess($ownerRecord), 403);

        parent::mount();
    }

    abstract protected function getOwnerModelClass(): string;

    protected function getOwnerRecord(): Model
    {
        $ownerRecord = $this->ownerModelClass::query()->find($this->owner);

        abort_unless($ownerRecord instanceof Model, 404);

        return $ownerRecord;
    }

    public function form(Schema $schema): Schema
    {
        $formClass = $this->getDocumentFormClass();

        return $formClass::configure($schema, $this->getOwnerRecord());
    }

    abstract protected function getDocumentFormClass(): string;

    public function getTitle(): string|Htmlable
    {
        $ownerContextLabel = DocumentOwnerRegistry::getOwnerContextLabel($this->getOwnerRecord()) ?? 'Kontext';

        return sprintf('Dokument hinzufügen · %s', $ownerContextLabel);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Document::user_id] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Document $document */
        $document = parent::handleRecordCreation($data);
        $ownerRecord = $this->getOwnerRecord();
        $relationshipName = DocumentOwnerRegistry::getRelationshipName($ownerRecord);

        abort_unless(is_string($relationshipName), 500);

        $ownerRecord->{$relationshipName}()->syncWithoutDetaching([$document->getKey()]);

        return $document;
    }

    protected function getRedirectUrl(): string
    {
        return DocumentOwnerRegistry::getOwnerViewUrl($this->getOwnerRecord()) ?? parent::getRedirectUrl();
    }
}


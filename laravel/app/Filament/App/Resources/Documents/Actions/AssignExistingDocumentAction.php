<?php

namespace App\Filament\App\Resources\Documents\Actions;

use App\Filament\App\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class AssignExistingDocumentAction
{
    const string ACTION_NAME = 'assignExistingDocument';
    const string FIELD_DOCUMENT_ID = 'document_id';

    public static function make(): Action
    {
        return Action::make(self::ACTION_NAME)
            ->label('Vorhandenes Dokument zuweisen')
            ->icon('heroicon-o-paper-clip')
            ->modalHeading('Dokument zuweisen')
            ->schema([
                Select::make(self::FIELD_DOCUMENT_ID)
                    ->label('Dokument')
                    ->options(fn(Model $record): array => self::getDocumentOptions($record))
                    ->searchable()
                    ->required(),
            ])
            ->action(function (Model $record, array $data): void {
                $relationshipName = DocumentOwnerRegistry::getRelationshipName($record);

                if (!is_string($relationshipName)) {
                    Notification::make()
                        ->title('Dieses Modell unterstützt keine Dokument-Verknüpfung.')
                        ->danger()
                        ->send();

                    return;
                }

                /** @var Document|null $document */
                $document = Document::query()
                    ->where(Document::user_id, auth()->id())
                    ->find($data[self::FIELD_DOCUMENT_ID] ?? null);

                if (!$document instanceof Document) {
                    Notification::make()
                        ->title('Dokument nicht gefunden.')
                        ->danger()
                        ->send();

                    return;
                }

                $record->{$relationshipName}()->syncWithoutDetaching([$document->getKey()]);

                Notification::make()
                    ->title('Dokument erfolgreich zugewiesen.')
                    ->success()
                    ->send();
            });
    }

    private static function getDocumentOptions(Model $owner): array
    {
        $relationshipName = DocumentOwnerRegistry::getRelationshipName($owner);

        if (!is_string($relationshipName)) {
            return [];
        }

        $alreadyAssignedIds = $owner->{$relationshipName}()
            ->pluck(Document::TABLE . '.' . Document::id)
            ->all();

        return Document::query()
            ->where(Document::user_id, auth()->id())
            ->when(!empty($alreadyAssignedIds), fn($query) => $query->whereNotIn(Document::id, $alreadyAssignedIds))
            ->orderBy(Document::created_at, 'desc')
            ->get()
            ->mapWithKeys(function (Document $document): array {
                $name = $document->{Document::filename} ?: basename((string)$document->{Document::path});

                return [
                    $document->getKey() => sprintf('%s (%s)', $name, $document->{Document::type} ?: 'ohne Typ'),
                ];
            })
            ->all();
    }
}


<?php

namespace App\Filament\App\Resources\Documents\Actions;

use App\Filament\App\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ManageDocumentLinksAction
{
    const string ACTION_NAME = 'manageLinks';
    const string FIELD_LINKS = 'links';
    const string FIELD_LINK_KEY = 'key';
    const string FIELD_LINK_LABEL = 'label';
    const string FIELD_OWNER_TYPE = 'owner_type';
    const string FIELD_OWNER_ID = 'owner_id';

    public static function make(): Action
    {
        return Action::make(self::ACTION_NAME)
            ->label('Verknüpfungen verwalten')
            ->icon('heroicon-o-link')
            ->fillForm(fn(Document $record): array => [
                self::FIELD_LINKS => self::getLinkRows($record),
            ])
            ->schema([
                Repeater::make(self::FIELD_LINKS)
                    ->label('Bestehende Verknüpfungen')
                    ->addable(false)
                    ->reorderable(false)
                    ->schema([
                        Hidden::make(self::FIELD_LINK_KEY),
                        TextInput::make(self::FIELD_LINK_LABEL)
                            ->label('Eintrag')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columnSpanFull(),
                Select::make(self::FIELD_OWNER_TYPE)
                    ->label('Modell')
                    ->options(DocumentOwnerRegistry::getTypeOptions())
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set(self::FIELD_OWNER_ID, null);
                    }),
                Select::make(self::FIELD_OWNER_ID)
                    ->label('Datensatz')
                    ->options(fn(Get $get, Document $record): array => DocumentOwnerRegistry::getOwnerOptionsForType(
                        $record,
                        $get(self::FIELD_OWNER_TYPE)
                    ))
                    ->searchable()
                    ->visible(fn(Get $get): bool => filled($get(self::FIELD_OWNER_TYPE))),
            ])
            ->action(function (Document $record, array $data): void {
                $submittedRows = $data[self::FIELD_LINKS] ?? [];
                $remainingKeys = collect($submittedRows)
                    ->pluck(self::FIELD_LINK_KEY)
                    ->filter()
                    ->map(fn($value): string => (string)$value)
                    ->values();

                $existingKeys = collect(array_keys(DocumentOwnerRegistry::getLinkedOwnerOptions($record)));
                $keysToRemove = $existingKeys->diff($remainingKeys);

                foreach ($keysToRemove as $linkKey) {
                    DocumentOwnerRegistry::detachLinkByKey($record, (string)$linkKey);
                }

                $wasAttached = DocumentOwnerRegistry::attachOwner(
                    $record,
                    $data[self::FIELD_OWNER_TYPE] ?? null,
                    $data[self::FIELD_OWNER_ID] ?? null,
                );

                Notification::make()
                    ->title($wasAttached || $keysToRemove->isNotEmpty()
                        ? 'Verknüpfungen aktualisiert.'
                        : 'Keine Änderungen vorgenommen.')
                    ->success()
                    ->send();
            });
    }

    private static function getLinkRows(Document $record): array
    {
        $rows = [];

        foreach (DocumentOwnerRegistry::getLinkedOwnerOptions($record) as $key => $label) {
            $rows[] = [
                self::FIELD_LINK_KEY => (string)$key,
                self::FIELD_LINK_LABEL => (string)$label,
            ];
        }

        return $rows;
    }
}


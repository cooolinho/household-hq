<?php

namespace App\Filament\Admin\Resources\Documents\Schemas;

use App\Filament\Admin\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\Document;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class DocumentForm
{
    public static function configure(Schema $schema, ?Model $ownerRecord = null): Schema
    {
        return $schema
            ->components([
                ...static::getContextComponents($ownerRecord),
                Section::make('Dokument')
                    ->description('Zeige nur die wichtigsten Felder an. Weitere Optionen findest du unten in den erweiterten Einstellungen.')
                    ->schema(static::getMainSchema())
                    ->columns(2),
                Section::make('Erweiterte Einstellungen')
                    ->description('Zusätzliche Felder für Sortierung, Klassifizierung und Organisation des Dokuments.')
                    ->schema(static::getAdvancedSchema($schema))
                    ->collapsible()
                    ->collapsed(),
                ...static::getHiddenSchema(),
            ]);
    }

    protected static function getContextComponents(?Model $ownerRecord): array
    {
        if (!$ownerRecord instanceof Model) {
            return [];
        }

        return [
            Section::make('Verknüpfung')
                ->description('Dieses Dokument wird direkt beim Speichern mit dem ausgewählten Datensatz verknüpft.')
                ->schema([
                    TextInput::make('document_owner_context')
                        ->label('Verknüpft mit')
                        ->default(DocumentOwnerRegistry::getOwnerContextLabel($ownerRecord))
                        ->disabled()
                        ->dehydrated(false),
                ]),
        ];
    }

    protected static function getMainSchema(): array
    {
        return [
            FileUpload::make(Document::path)
                ->disk(Document::STORAGE_DISK)
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain'
                ])
                ->maxSize(15360)
                ->getUploadedFileNameForStorageUsing(function ($file) {
                    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $timestamp = now()->timestamp;

                    return "{$name}_{$timestamp}.{$extension}";
                })
                ->required()
                ->columnSpanFull()
                ->visible(fn($get, $record) => is_null($record)),
            TextInput::make(Document::filename),
            TextInput::make(Document::type),
            Textarea::make(Document::description)
                ->columnSpanFull(),
        ];
    }

    protected static function getAdvancedSchema(Schema $schema): array
    {
        return [
            TextInput::make(Document::sort)
                ->required()
                ->numeric()
                ->default(0),
            TagResource::getMorphToManySelect($schema, Document::morph_to_many_tags),
        ];
    }

    protected static function getHiddenSchema(): array
    {
        return [
            Hidden::make(Document::user_id)
                ->default(fn() => auth()->id()),
        ];
    }
}

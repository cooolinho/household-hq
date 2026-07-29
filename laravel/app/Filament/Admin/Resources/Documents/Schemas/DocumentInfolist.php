<?php

namespace App\Filament\Admin\Resources\Documents\Schemas;

use App\Filament\Admin\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Models\Document;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('documentable_context')
                    ->label('Verknüpft mit')
                    ->state(fn(Document $record): string => DocumentOwnerRegistry::getDocumentContextLabel($record) ?? 'Nicht verknüpft'),
                TextEntry::make(Document::type)
                    ->label(__('admin.resource.document.fields.type'))
                    ->placeholder(__('admin.resource.document.placeholders.empty')),
                TextEntry::make(Document::filename)
                    ->label(__('admin.resource.document.fields.filename'))
                    ->placeholder(__('admin.resource.document.placeholders.empty')),
                TextEntry::make(Document::description)
                    ->label(__('admin.resource.document.fields.description'))
                    ->placeholder(__('admin.resource.document.placeholders.empty'))
                    ->columnSpanFull(),
                TextEntry::make(Document::file_size)
                    ->label(__('admin.resource.document.fields.file_size'))
                    ->numeric()
                    ->placeholder(__('admin.resource.document.placeholders.empty')),
                TextEntry::make(Document::mime_type)
                    ->label(__('admin.resource.document.fields.mime_type'))
                    ->placeholder(__('admin.resource.document.placeholders.empty')),
                TextEntry::make(Document::sort)
                    ->label(__('admin.resource.document.fields.sort'))
                    ->numeric()
                    ->placeholder(__('admin.resource.document.placeholders.empty')),
                TextEntry::make(Document::created_at)
                    ->label(__('admin.resource.document.fields.created_at'))
                    ->dateTime()
                    ->placeholder(__('admin.resource.document.placeholders.empty')),
                TextEntry::make(Document::updated_at)
                    ->label(__('admin.resource.document.fields.updated_at'))
                    ->dateTime()
                    ->placeholder(__('admin.resource.document.placeholders.empty')),
            ]);
    }
}

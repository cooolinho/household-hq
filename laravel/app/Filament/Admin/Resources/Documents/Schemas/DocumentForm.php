<?php

namespace App\Filament\Admin\Resources\Documents\Schemas;

use App\Models\Document;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentForm
{
    const string QUERY_PARAMS = 'query-params';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make(self::QUERY_PARAMS)
                    ->default(fn() => request()->all()),
                Hidden::make(Document::user_id)
                    ->default(fn() => auth()->id()),
                TextInput::make(Document::type),
                TextInput::make(Document::filename),
                FileUpload::make(Document::path)
                    ->disk(Document::STORAGE_DISK)
                    // custom validation: only allow pdf, doc, docx, xls, xlsx, ppt, pptx, txt
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
                    ->maxSize(15360) // max 15MB
                    // custom filename generation to avoid collisions
                    ->getUploadedFileNameForStorageUsing(function ($file) {
                        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension = $file->getClientOriginalExtension();
                        $timestamp = now()->timestamp;
                        return "{$name}_{$timestamp}.{$extension}";
                    })
                    ->required()
                    // only show when creating
                    ->visible(fn($get, $record) => is_null($record)),
                Textarea::make(Document::description)
                    ->columnSpanFull(),
                TextInput::make(Document::sort)
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}

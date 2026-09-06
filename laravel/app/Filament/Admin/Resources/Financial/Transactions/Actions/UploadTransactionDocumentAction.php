<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Actions;

use App\Filament\Admin\Resources\Financial\Transactions\TransactionResource;
use App\Models\Document;
use App\Models\Financial\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class UploadTransactionDocumentAction
{
    public static function make(): Action
    {
        return Action::make('uploadTransactionDocument')
            ->label('Dokument hochladen')
            ->icon(Heroicon::OutlinedPaperClip)
            ->modalHeading('Dokument zur Transaktion hochladen')
            ->modalWidth('2xl')
            ->schema([
                FileUpload::make(Document::path)
                    ->label('Datei')
                    ->disk(Document::STORAGE_DISK)
                    ->directory('transactions')
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'application/pdf',
                    ])
                    ->maxSize(15360)
                    ->storeFileNamesIn(Document::filename)
                    ->required(),
                Section::make('Optionale Angaben')
                    ->schema([
                        Textarea::make(Document::description)
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ])
            ->modalSubmitActionLabel('Hochladen')
            ->modalCancelActionLabel('Abbrechen')
            ->action(function (Transaction $record, array $data): void {
                abort_unless(TransactionResource::canView($record), 403);

                $path = $data[Document::path] ?? null;
                if (!is_string($path) || blank($path)) {
                    throw ValidationException::withMessages([
                        Document::path => 'Bitte wähle eine gültige Datei aus.',
                    ]);
                }

                $filename = $data[Document::filename] ?? basename($path);
                if (!is_string($filename) || blank($filename)) {
                    $filename = basename($path);
                }

                $document = Document::query()->create([
                    Document::user_id => auth()->id(),
                    Document::path => $path,
                    Document::filename => $filename,
                    Document::description => $data[Document::description] ?? null,
                ]);

                $record->documents()->syncWithoutDetaching([$document->getKey()]);

                Notification::make()
                    ->title('Dokument erfolgreich hochgeladen.')
                    ->success()
                    ->send();
            });
    }
}

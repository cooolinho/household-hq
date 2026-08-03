<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\Actions\ManageDocumentLinksAction;
use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected string $view = 'filament.admin.resources.documents.pages.view-document';

    public function getTitle(): string|Htmlable
    {
        return 'Dokument Ansehen';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => route('admin.documents.download', $this->record))
                ->openUrlInNewTab(),
            ManageDocumentLinksAction::make(),
            EditAction::make(),
        ];
    }

    protected function getViewData(): array
    {
        /** @var Document $document */
        $document = $this->record;

        $mimeType = $document->mime_type;
        $fileExtension = $document->filename
            ? strtolower(pathinfo($document->filename, PATHINFO_EXTENSION))
            : null;

        $previewableMimes = [
            'application/pdf',
            'image/jpeg', 'image/jpg', 'image/png',
            'image/gif', 'image/webp', 'image/svg+xml',
            'text/plain', 'text/html', 'text/csv',
        ];
        $previewableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'txt', 'html', 'csv'];

        $isImage = str_starts_with($mimeType ?? '', 'image/')
            || in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        $isPdf = $mimeType === 'application/pdf' || $fileExtension === 'pdf';
        $isText = str_starts_with($mimeType ?? '', 'text/')
            || in_array($fileExtension, ['txt', 'html', 'csv']);

        $canPreview = $document->path && (
                in_array($mimeType, $previewableMimes)
                || in_array($fileExtension, $previewableExtensions)
            );

        $textContent = null;
        if ($canPreview && $isText && $document->path) {
            try {
                $disk = Storage::disk(Document::STORAGE_DISK);
                if ($disk->exists($document->path)) {
                    $textContent = $disk->get($document->path);
                }
            } catch (\Exception) {
                // kein Textinhalt verfügbar
            }
        }

        return [
            'document' => $document,
            'fileUrl' => $document->path ? route('admin.documents.view', $document) : null,
            'fileExtension' => $fileExtension,
            'canPreview' => $canPreview,
            'isImage' => $isImage,
            'isPdf' => $isPdf,
            'isText' => $isText,
            'textContent' => $textContent,
            'linkedOwners' => DocumentOwnerRegistry::getLinkedOwners($document),
        ];
    }
}

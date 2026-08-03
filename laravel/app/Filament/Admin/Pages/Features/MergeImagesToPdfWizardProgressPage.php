<?php

namespace App\Filament\Admin\Pages\Features;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class MergeImagesToPdfWizardProgressPage extends Page
{
    const string SLUG = 'merge-images-to-pdf-processing';
    const string ROUTE_NAME = 'filament.admin.pages.merge-images-to-pdf-processing-keyed';
    protected static ?string $slug = self::SLUG;
    protected static ?string $title = 'PDF-Erstellung';
    protected static bool $shouldRegisterNavigation = false;
    public ?string $processingCacheKey = null;
    public string $jobStatus = 'processing';
    protected string $view = 'filament.admin.pages.merge-images-to-pdf-processing';

    public function mount(?string $processingCacheKey = null): void
    {
        $this->processingCacheKey = $processingCacheKey;

        if (!$processingCacheKey) {
            $this->redirect(MergeImagesToPdfWizardPage::getUrl());
        }
    }

    /**
     * Wird alle 2 Sekunden per wire:poll gerufen, solange der Job läuft.
     * Leitet bei Erfolg auf das erstellte Dokument weiter.
     * Bei Fehler oder Timeout wird eine Benachrichtigung angezeigt.
     */
    public function checkJobStatus(): void
    {
        if ($this->processingCacheKey === null || $this->jobStatus !== 'processing') {
            return;
        }

        $result = Cache::get($this->processingCacheKey);

        if ($result === null) {
            // Cache abgelaufen → Timeout
            $this->jobStatus = 'idle';
            $this->processingCacheKey = null;
            Notification::make()
                ->title('Zeitüberschreitung')
                ->body('Der Vorgang hat zu lange gedauert. Bitte erneut versuchen.')
                ->danger()
                ->send();

            $this->redirect(MergeImagesToPdfWizardPage::getUrl());
            return;
        }

        if (($result['status'] ?? '') === 'done') {
            Cache::forget($this->processingCacheKey);
            $this->redirect(
                DocumentResource::getUrl('view', ['record' => $result['documentId']])
            );
            return;
        }

        if (($result['status'] ?? '') === 'error') {
            $this->jobStatus = 'idle';
            Cache::forget($this->processingCacheKey);
            $this->processingCacheKey = null;
            Notification::make()
                ->title('Fehler bei der PDF-Erstellung')
                ->body($result['message'] ?? 'Unbekannter Fehler')
                ->danger()
                ->send();

            $this->redirect(MergeImagesToPdfWizardPage::getUrl());
        }
    }
}

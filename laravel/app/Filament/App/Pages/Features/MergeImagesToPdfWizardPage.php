<?php

namespace App\Filament\App\Pages\Features;

use App\Filament\App\Resources\Documents\DocumentResource;
use App\Jobs\MergeImagesToPdfJob;
use App\Menu\NavigationGroup;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
use App\Services\ImageMergePdfService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class MergeImagesToPdfWizardPage extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.app.pages.features.merge-images-to-pdf-wizard-page';
    protected static ?string $title = 'Bilder zu PDF';
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::FEATURES;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-photo';
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationLabel = 'Bilder zu PDF';

    // ── Upload (Step 1) ───────────────────────────────────────────────────────

    /** @var mixed[] Livewire TemporaryUploadedFile[] – wird nach Verarbeitung geleert */
    public array $uploadedImages = [];

    // ── Reihenfolge (Step 2) ─────────────────────────────────────────────────

    /** @var array<int, array{path: string, filename: string, name: string}> */
    public array $orderedImages = [];

    // ── Metadaten (Step 3) ───────────────────────────────────────────────────

    public string $description = '';

    /** @var array<int, int|string> */
    public array $tags = [];

    public bool $useDescriptionAsFilename = false;

    // ── Job-Status ───────────────────────────────────────────────────────────

    public ?string $processingCacheKey = null;

    /** idle | processing | done */
    public string $jobStatus = 'idle';


    // ─────────────────────────────────────────────────────────────────────────
    // Schema
    // ─────────────────────────────────────────────────────────────────────────

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Bilder hochladen')
                    ->description('JPG- und PNG-Bilder hochladen.')
                    ->afterValidation(function (): void {
                        if (!empty($this->uploadedImages)) {
                            $this->validate(
                                ['uploadedImages.*' => ['mimes:jpg,jpeg,png', 'max:20480']],
                                ['uploadedImages.*.mimes' => 'Nur JPG und PNG erlaubt.', 'uploadedImages.*.max' => 'Maximalgröße: 20 MB.']
                            );
                            $this->processUploadedImages();
                        }
                        if (empty($this->orderedImages)) {
                            $this->validate(
                                ['orderedImages' => ['required', 'array', 'min:1']],
                                ['orderedImages.required' => 'Bitte mindestens ein Bild hochladen.', 'orderedImages.min' => 'Bitte mindestens ein Bild hochladen.']
                            );
                        }
                    })
                    ->schema([
                        View::make('filament.app.pages.merge-images-to-pdf-wizard-page-step-1'),
                    ]),

                Step::make('Reihenfolge')
                    ->description('Reihenfolge anpassen und Bilder optional entfernen.')
                    ->beforeValidation(function (): void {
                        $this->validate(
                            ['orderedImages' => ['required', 'array', 'min:1']],
                            ['orderedImages.required' => 'Mindestens ein Bild erforderlich.', 'orderedImages.min' => 'Mindestens ein Bild erforderlich.']
                        );
                    })
                    ->schema([
                        View::make('filament.app.pages.merge-images-to-pdf-wizard-page-step-2'),
                    ]),

                Step::make('Dokument')
                    ->description('Beschreibung und Tags für das Dokument festlegen.')
                    ->schema([
                        View::make('filament.app.pages.merge-images-to-pdf-wizard-page-step-3'),
                    ]),
            ])
                ->persistStepInQueryString('pdfStep')
                ->submitAction(
                    Action::make('submit')
                        ->label('PDF generieren')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('primary')
                        ->button()
                        ->action('submit')

                ),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Livewire-Lifecycle
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verschiebt alle frisch hochgeladenen Livewire-Temp-Dateien in den
     * permanenten Temp-Ordner auf dem Document::STORAGE_DISK und befüllt
     * $orderedImages. Löscht danach $uploadedImages.
     */
    private function processUploadedImages(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $disk = Storage::disk(Document::STORAGE_DISK);

        foreach ($this->uploadedImages as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $safeName = uniqid('img_', true) . '.' . $ext;
            $fullPath = 'temp/' . $user->id . '/' . $safeName;

            $disk->put($fullPath, $file->get());

            $this->orderedImages[] = [
                'path' => $fullPath,
                'filename' => $safeName,
                'name' => $file->getClientOriginalName(),
            ];
        }

        $this->uploadedImages = [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Dateiname-Vorschau
    // ─────────────────────────────────────────────────────────────────────────

    /** Checkbox automatisch deaktivieren, wenn Beschreibung leer wird */
    public function updatedDescription(): void
    {
        if (empty($this->description)) {
            $this->useDescriptionAsFilename = false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reihenfolge-Methoden (Step 2)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Gibt die Vorschau des Dateinamens zurück.
     * {zeitstempel} ist ein Platzhalter, der beim tatsächlichen Generieren
     * durch das aktuelle Datum/Uhrzeit (Ymd_His) ersetzt wird.
     */
    public function getFilenamePreview(): string
    {
        if (!$this->useDescriptionAsFilename || empty($this->description)) {
            return '';
        }
        $base = app(ImageMergePdfService::class)->sanitizeFilename($this->description);
        if ($base === '') {
            return 'scan_{zeitstempel}.pdf';
        }
        return $base . '_{zeitstempel}.pdf';
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->orderedImages)) {
            return;
        }
        [$this->orderedImages[$index - 1], $this->orderedImages[$index]] = [
            $this->orderedImages[$index],
            $this->orderedImages[$index - 1],
        ];
        $this->orderedImages = array_values($this->orderedImages);
    }

    public function moveDown(int $index): void
    {
        $count = count($this->orderedImages);
        if ($index < 0 || $index >= $count - 1) {
            return;
        }
        [$this->orderedImages[$index], $this->orderedImages[$index + 1]] = [
            $this->orderedImages[$index + 1],
            $this->orderedImages[$index],
        ];
        $this->orderedImages = array_values($this->orderedImages);
    }

    public function removeImage(int $index): void
    {
        if ($index < 0 || $index >= count($this->orderedImages)) {
            return;
        }
        array_splice($this->orderedImages, $index, 1);
        $this->orderedImages = array_values($this->orderedImages);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tags (Step 3)
    // ─────────────────────────────────────────────────────────────────────────

    /** Drag-and-Drop: Bild von $from nach $to verschieben */
    public function reorder(int $from, int $to): void
    {
        $count = count($this->orderedImages);
        if ($from === $to || $from < 0 || $from >= $count || $to < 0 || $to >= $count) {
            return;
        }
        $item = array_splice($this->orderedImages, $from, 1)[0];
        array_splice($this->orderedImages, $to, 0, [$item]);
        $this->orderedImages = array_values($this->orderedImages);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Submit & Job-Status
    // ─────────────────────────────────────────────────────────────────────────

    /** @return Collection<int, Tag> */
    public function getAvailableTags(): Collection
    {
        return Tag::where(Tag::user_id, auth()->id())
            ->orderBy(Tag::order_column)
            ->orderBy(Tag::id)
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function submit(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $this->validate(
            ['orderedImages' => ['required', 'array', 'min:1']],
            ['orderedImages.required' => 'Mindestens ein Bild erforderlich.']
        );

        $cacheKey = 'merge_pdf_job_' . $user->id . '_' . uniqid();

        // Initials Status setzen, bevor der Job dispatched wird
        Cache::put($cacheKey, ['status' => 'processing'], now()->addMinutes(15));

        MergeImagesToPdfJob::dispatch(
            userId: $user->id,
            orderedImages: $this->orderedImages,
            description: $this->description ?: null,
            tagIds: array_map('intval', $this->tags),
            useDescriptionAsFilename: $this->useDescriptionAsFilename,
            cacheKey: $cacheKey,
        );

        $this->processingCacheKey = $cacheKey;
        $this->jobStatus = 'processing';

        Notification::make()
            ->title('PDF-Erstellung gestartet')
            ->body('Die Bilder werden nun zu einem PDF zusammengeführt. Bitte warten...')
            ->info()
            ->send();
    }

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

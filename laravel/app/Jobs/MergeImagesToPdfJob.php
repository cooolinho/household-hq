<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Tag;
use App\Services\ImageMergePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MergeImagesToPdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $userId
     * @param array<int, array{path: string, filename: string, name: string}> $orderedImages
     * @param string|null $description
     * @param array<int, int> $tagIds
     * @param bool $useDescriptionAsFilename
     * @param string $cacheKey
     */
    public function __construct(
        public readonly int     $userId,
        public readonly array   $orderedImages,
        public readonly ?string $description,
        public readonly array   $tagIds,
        public readonly bool    $useDescriptionAsFilename,
        public readonly string  $cacheKey,
    )
    {
    }

    public function handle(ImageMergePdfService $service): void
    {
        try {
            $pdfPath = $service->generate(
                orderedImages: $this->orderedImages,
                userId: $this->userId,
                description: $this->description,
                useDescriptionAsFilename: $this->useDescriptionAsFilename,
            );
            $filename = basename($pdfPath);

            /** @var Document $document */
            $document = Document::query()->create([
                Document::user_id => $this->userId,
                Document::type => 'scan',
                Document::path => $pdfPath,
                Document::filename => $filename,
                Document::description => $this->description,
                Document::sort => 0,
                Document::mime_type => 'application/pdf',
            ]);

            if (!empty($this->tagIds)) {
                $tags = Tag::whereIn('id', $this->tagIds)->get();
                $document->syncTags($tags);
            }

            Cache::put($this->cacheKey, [
                'status' => 'done',
                'documentId' => $document->id,
            ], now()->addMinutes(10));

        } catch (Throwable $e) {
            Cache::put($this->cacheKey, [
                'status' => 'error',
                'message' => $e->getMessage(),
            ], now()->addMinutes(10));
            Log::error('Fehler beim Zusammenführen von Bildern zu PDF: ' . $e->getMessage(), [
                'exception' => $e,
                'userId' => $this->userId,
            ]);
        } finally {
            // Temporäre Upload-Bilder bereinigen
            $disk = Storage::disk(Document::STORAGE_DISK);
            foreach ($this->orderedImages as $img) {
                try {
                    if ($disk->exists($img['path'])) {
                        $disk->delete($img['path']);
                    }
                } catch (Throwable) {
                    // ignorieren – kein kritischer Fehler
                }
            }
        }
    }
}


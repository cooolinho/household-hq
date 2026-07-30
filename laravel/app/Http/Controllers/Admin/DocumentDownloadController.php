<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    /**
     * Download a document file
     */
    public function download(Document $document): Response|StreamedResponse
    {
        try {
            $disk = Storage::disk(Document::STORAGE_DISK);

            if (!$disk->exists($document->path)) {
                abort(404, 'Datei nicht gefunden');
            }

            return $disk->download(
                $document->path,
                $document->filename ?? basename($document->path)
            );

        } catch (\Exception $e) {
            abort(500, 'Fehler beim Herunterladen der Datei: ' . $e->getMessage());
        }
    }
}

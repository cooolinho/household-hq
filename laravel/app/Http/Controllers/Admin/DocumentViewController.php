<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DocumentViewController extends Controller
{
    /**
     * View/preview a document file (inline display)
     */
    public function view(Document $document): Response
    {
        try {
            $disk = Storage::disk(Document::STORAGE_DISK);

            if (!$disk->exists($document->path)) {
                abort(404, 'Datei nicht gefunden');
            }

            $content = $disk->get($document->path);
            $mimeType = $document->mime_type ?? 'application/octet-stream';

            return response($content, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . ($document->filename ?? 'file') . '"');

        } catch (\Exception $e) {
            abort(500, 'Fehler beim Laden der Datei: ' . $e->getMessage());
        }
    }
}

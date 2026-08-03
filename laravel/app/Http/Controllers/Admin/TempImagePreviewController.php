<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TempImagePreviewController extends Controller
{
    /**
     * Liefert ein temporäres Upload-Bild des aktuell eingeloggten Benutzers.
     * Der Pfad wird aus dem Auth-User-ID und dem Dateinamen zusammengesetzt,
     * sodass kein User auf Bilder eines anderen Users zugreifen kann.
     */
    public function preview(string $filename): Response
    {
        // Sicherheit: Dateiname darf keine Verzeichnis-Traversal enthalten
        $filename = basename($filename);

        $userId = (int)auth()->id();
        $path = 'temp/' . $userId . '/' . $filename;

        $disk = Storage::disk(Document::STORAGE_DISK);

        if (!$disk->exists($path)) {
            abort(404, 'Vorschaubild nicht gefunden');
        }

        $content = $disk->get($path);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        return response($content ?? '', 200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'private, max-age=300');
    }
}


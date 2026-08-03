<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageMergePdfService
{
    /**
     * Generiert ein PDF aus den übergebenen Bildern (je Bild eine A4-Seite).
     * Bilder liegen auf dem Document::STORAGE_DISK unter den angegebenen Pfaden.
     * Gibt den Storage-Pfad des erzeugten PDFs zurück.
     *
     * @param array<int, array{path: string, filename: string, name: string}> $orderedImages
     * @param int $userId
     * @param string|null $description
     * @param bool $useDescriptionAsFilename
     * @return string Storage-Pfad des PDFs
     */
    public function generate(
        array   $orderedImages,
        int     $userId,
        ?string $description = null,
        bool    $useDescriptionAsFilename = false,
    ): string
    {
        $disk = Storage::disk(Document::STORAGE_DISK);

        $imageData = [];
        foreach ($orderedImages as $item) {
            $path = $item['path'];
            if (!$disk->exists($path)) {
                continue;
            }
            $content = $disk->get($path);
            if ($content === null) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimeType = match ($ext) {
                'png' => 'image/png',
                default => 'image/jpeg',
            };
            $imageData[] = [
                'data_uri' => "data:{$mimeType};base64," . base64_encode($content),
                'name' => $item['name'],
            ];
        }

        if (empty($imageData)) {
            throw new RuntimeException('Keine Bilder zum Zusammenführen gefunden.');
        }

        $html = $this->buildHtml($imageData);
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $pdfBinary = $pdf->output();

        $filename = $this->buildFilename($description, $useDescriptionAsFilename);
        $storagePath = sprintf('scan-pdf/%d/%s', $userId, $filename);

        $disk->put($storagePath, $pdfBinary);

        return $storagePath;
    }

    /**
     * @param array<int, array{data_uri: string, name: string}> $images
     */
    private function buildHtml(array $images): string
    {
        $pages = '';
        foreach ($images as $i => $img) {
            $isLast = ($i === count($images) - 1);
            $pageBreak = $isLast ? '' : 'page-break-after:always;';
            $pages .= '<div style="width:100%;text-align:center;' . $pageBreak . '">'
                . '<img src="' . $img['data_uri']
                . '" alt="Seite ' . ($i + 1) . '"'
                . ' style="max-width:100%;max-height:257mm;width:auto;height:auto;" />'
                . '</div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 10mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; }
</style>
</head>
<body>
{$pages}
</body>
</html>
HTML;
    }

    public function buildFilename(?string $description, bool $useDescriptionAsFilename): string
    {
        $timestamp = now()->format('Ymd_His');

        if ($useDescriptionAsFilename && !empty($description)) {
            $base = $this->sanitizeFilename($description);
            if ($base !== '') {
                return "{$base}_{$timestamp}.pdf";
            }
        }

        return "scan_{$timestamp}.pdf";
    }

    /**
     * Wandelt einen beliebigen Text in einen dateisystemtauglichen Dateinamen um:
     * - Kleingeschrieben
     * - Umlaute ersetzt (ä→ae, ö→oe, ü→ue, ß→ss)
     * - Nicht-ASCII per Transliteration entfernt
     * - Nur a-z, 0-9, _ und - erlaubt
     * - Mehrfache Sonderzeichen zusammengefasst
     * - Max. 120 Zeichen
     */
    public function sanitizeFilename(string $input): string
    {
        $input = mb_strtolower($input);

        // Umlaute ersetzen
        $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss'];
        $input = str_replace(array_keys($map), array_values($map), $input);

        // Restliche Nicht-ASCII-Zeichen transliterieren
        $input = Str::ascii($input);

        // Unsichere Zeichen durch Unterstrich ersetzen
        $input = preg_replace('/[^a-z0-9_\-]/', '_', $input) ?? '';

        // Mehrfache Unterstriche/Bindestriche zusammenfassen
        $input = preg_replace('/[_\-]{2,}/', '_', $input) ?? '';

        // Rand bereinigen
        $input = trim($input, '_-');

        return mb_substr($input, 0, 120);
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Financial\Goal;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class GoalImageController extends Controller
{
    public function show(Goal $goal): Response
    {
        if ($goal->user_id !== auth()->id()) {
            abort(404, 'Bild nicht gefunden');
        }

        $path = (string)$goal->image_path;

        if (blank($path) || str_contains($path, '..')) {
            abort(404, 'Bild nicht gefunden');
        }

        foreach ([Goal::STORAGE_DISK, config('filesystems.default')] as $disk) {
            $storage = Storage::disk($disk);

            if (!$storage->exists($path)) {
                continue;
            }

            $mimeType = $storage->mimeType($path) ?: 'application/octet-stream';
            $content = $storage->get($path);

            return response($content, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . basename($path) . '"')
                ->header('Cache-Control', 'private, max-age=300');
        }

        abort(404, 'Bild nicht gefunden');
    }
}

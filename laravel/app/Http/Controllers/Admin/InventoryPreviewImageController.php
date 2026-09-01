<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Article;
use App\Models\Inventory\ArticleImage;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InventoryPreviewImageController extends Controller
{
    public function show(string $type, int $record): Response
    {
        [$path, $disks] = match ($type) {
            'collection' => $this->resolveCollectionImage($record),
            'location' => $this->resolveLocationImage($record),
            'article-image' => $this->resolveArticleImage($record),
            default => abort(404, 'Unbekannter Bildtyp'),
        };

        if (blank($path) || str_contains((string)$path, '..')) {
            abort(404, 'Bild nicht gefunden');
        }

        foreach ($disks as $disk) {
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

    /**
     * @return array{0:string,1:array<int,string>}
     */
    private function resolveCollectionImage(int $collectionId): array
    {
        $collection = Collection::query()
            ->where(Collection::id, $collectionId)
            ->where(Collection::user_id, auth()->id())
            ->firstOrFail();

        return [
            (string)$collection->{Collection::preview_image},
            [Collection::STORAGE_DISK, config('filesystems.default')],
        ];
    }

    /**
     * @return array{0:string,1:array<int,string>}
     */
    private function resolveLocationImage(int $locationId): array
    {
        $location = Location::query()
            ->where(Location::id, $locationId)
            ->whereHas(
                Location::belongs_to_collection,
                fn(Builder $query) => $query->where(Collection::user_id, auth()->id())
            )
            ->firstOrFail();

        return [
            (string)$location->{Location::preview_image},
            [Location::STORAGE_DISK, config('filesystems.default')],
        ];
    }

    /**
     * @return array{0:string,1:array<int,string>}
     */
    private function resolveArticleImage(int $articleImageId): array
    {
        $articleImage = ArticleImage::query()
            ->where(ArticleImage::id, $articleImageId)
            ->whereHas(
                ArticleImage::belongs_to_article . '.' . Article::belongs_to_location . '.' . Location::belongs_to_collection,
                fn(Builder $query) => $query->where(Collection::user_id, auth()->id())
            )
            ->firstOrFail();

        return [
            $articleImage->{ArticleImage::path},
            [ArticleImage::STORAGE_DISK, config('filesystems.default')],
        ];
    }
}

<?php

namespace App\Filament\App\Widgets;

use App\Models\Inventory\Article;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class ArticleGalleryWidget extends \Filament\Widgets\Widget
{
    public int|string|array $columnSpan = 'full';

    #[Locked]
    public ?Model $record = null;

    protected string $view = 'filament.app.widgets.article-gallery-widget';

    protected function getViewData(): array
    {
        if (!$this->record instanceof Article) {
            return [
                'imageUrls' => [asset('article.jpg')],
            ];
        }

        $imageUrls = $this->record->previewImages
            ->map(fn($image) => route('app.inventory.preview', ['type' => 'article-image', 'record' => $image->getKey()]))
            ->values();

        if ($imageUrls->isEmpty()) {
            $imageUrls = collect([asset('article.jpg')]);
        }

        return [
            'imageUrls' => $imageUrls,
        ];
    }
}

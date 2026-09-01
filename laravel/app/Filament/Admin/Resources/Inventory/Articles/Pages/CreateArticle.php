<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Pages;

use App\Filament\Admin\Pages\InventorySetupPage;
use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    public function mount(): void
    {
        parent::mount();

        $hasLocation = Location::query()
            ->whereHas(
                Location::belongs_to_collection,
                fn($query) => $query->where(Collection::user_id, auth()->id())
            )
            ->exists();

        if ($hasLocation) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('Bitte zuerst Collection und Standort anlegen.')
            ->body('Nutze die Seite "Inventar starten", um die Grundstruktur anzulegen.')
            ->send();

        $this->redirect(InventorySetupPage::getUrl());
    }
}

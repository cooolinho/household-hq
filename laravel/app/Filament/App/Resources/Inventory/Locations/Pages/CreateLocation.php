<?php

namespace App\Filament\App\Resources\Inventory\Locations\Pages;

use App\Filament\App\Pages\InventorySetupPage;
use App\Filament\App\Resources\Inventory\Locations\LocationResource;
use App\Models\Inventory\Collection;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    public function mount(): void
    {
        parent::mount();

        $hasCollection = Collection::query()
            ->where(Collection::user_id, auth()->id())
            ->exists();

        if ($hasCollection) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('Bitte zuerst eine Collection anlegen.')
            ->body('Nutze die Seite "Inventar starten", um die Grundstruktur anzulegen.')
            ->send();

        $this->redirect(InventorySetupPage::getUrl());
    }
}

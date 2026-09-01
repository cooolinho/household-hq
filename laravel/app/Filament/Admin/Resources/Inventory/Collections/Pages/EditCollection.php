<?php

namespace App\Filament\Admin\Resources\Inventory\Collections\Pages;

use App\Filament\Admin\Resources\Inventory\Collections\CollectionResource;
use App\Filament\Admin\Resources\Inventory\Locations\Schemas\LocationForm;
use App\Models\Inventory\Location;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addLocation')
                ->label('Standort hinzufügen')
                ->icon('heroicon-o-map-pin')
                ->schema(LocationForm::components((int)$this->getRecord()->getKey()))
                ->action(function (array $data): void {
                    Location::query()->create([
                        Location::collection_id => (int)$this->getRecord()->getKey(),
                        Location::name => trim((string)$data[Location::name]),
                        Location::description => filled($data[Location::description] ?? null)
                            ? trim((string)$data[Location::description])
                            : null,
                        Location::preview_image => $data[Location::preview_image] ?? null,
                    ]);

                    Notification::make()
                        ->title('Standort wurde angelegt.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                }),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

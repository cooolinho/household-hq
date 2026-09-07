<?php

namespace App\Filament\App\Resources\Inventory\Collections\Pages;

use App\Filament\App\Resources\Inventory\Collections\CollectionResource;
use App\Models\Inventory\Collection;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ListCollections extends ListRecords
{
    protected static string $resource = CollectionResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.app.resources.inventory.collections.pages.list-collections'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return EloquentCollection<int, Collection>
     */
    public function getCollectionsProperty(): EloquentCollection
    {
        return Collection::query()
            ->where(Collection::user_id, auth()->id())
            ->withCount(Collection::has_many_locations)
            ->orderBy(Collection::name)
            ->get();
    }
}

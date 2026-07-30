<?php

namespace App\Filament\Admin\Resources\Inventory\Collections\Schemas;

use App\Models\Inventory\Collection;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(Collection::name)
                    ->required(),
            ]);
    }
}

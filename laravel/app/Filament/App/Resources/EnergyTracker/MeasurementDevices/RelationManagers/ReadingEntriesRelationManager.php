<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\RelationManagers;

use App\Filament\App\Resources\EnergyTracker\ReadingEntries\ReadingEntryResource;
use App\Filament\App\Resources\EnergyTracker\ReadingEntries\Schemas\ReadingEntryForm;
use App\Filament\App\Resources\EnergyTracker\ReadingEntries\Tables\ReadingEntriesTable;
use App\Models\EnergyTracker\MeasurementDevice;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ReadingEntriesRelationManager extends RelationManager
{
    protected static string $relationship = MeasurementDevice::has_many_reading_entries;

    public function form(Schema $schema): Schema
    {
        return ReadingEntryForm::configure($schema, $this->ownerRecord);
    }

    public function infolist(Schema $schema): Schema
    {
        return ReadingEntryResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return ReadingEntriesTable::configure($table)
            ->headerActions([
                CreateAction::make()
            ]);
    }
}

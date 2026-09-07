<?php

namespace App\Filament\App\Resources\EnergyTracker\ReadingEntries;

use App\Filament\App\Resources\EnergyTracker\ReadingEntries\Schemas\ReadingEntryForm;
use App\Filament\App\Resources\EnergyTracker\ReadingEntries\Tables\ReadingEntriesTable;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReadingEntryResource extends Resource
{
    protected static ?string $model = ReadingEntry::class;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.reading_entry.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.reading_entry.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.reading_entry.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return ReadingEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReadingEntriesTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->whereHas(ReadingEntry::belongs_to_measurement_device, function ($builder) {
                    $builder->where(MeasurementDevice::user_id, auth()->id());
                });
            });
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof ReadingEntry) {
            return $record->measurementDevice->user_id === auth()->id();
        }

        return false;
    }
}

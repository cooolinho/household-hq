<?php

namespace App\Filament\Admin\Resources\EnergyTracker\ReadingEntries;

use App\Filament\Admin\Resources\EnergyTracker\ReadingEntries\Schemas\ReadingEntryForm;
use App\Filament\Admin\Resources\EnergyTracker\ReadingEntries\Tables\ReadingEntriesTable;
use App\Models\EnergyTracker\ReadingEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReadingEntryResource extends Resource
{
    protected static ?string $model = ReadingEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = ReadingEntry::id;

    public static function form(Schema $schema): Schema
    {
        return ReadingEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReadingEntriesTable::configure($table);
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

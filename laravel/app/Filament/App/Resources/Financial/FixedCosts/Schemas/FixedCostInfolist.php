<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Schemas;

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FixedCostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(FixedCost::name),
                TextEntry::make(FixedCost::amount)
                    ->numeric(),
                TextEntry::make('category.name')
                    ->label('Kategorie')
                    ->badge()
                    ->placeholder('Nicht kategorisiert'),
                TextEntry::make(FixedCost::interval)
                    ->badge()
                    ->formatStateUsing(fn(FixedCost $record): string => self::formatInterval($record)),
                TextEntry::make(FixedCost::ends_mode)
                    ->badge(),
                TextEntry::make(FixedCost::ends_date)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::extended_date)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::extended_interval)
                    ->badge()
                    ->formatStateUsing(fn(FixedCost $record): string => self::formatInterval($record, true)),
                TextEntry::make(FixedCost::next_booking_date)
                    ->label('Nächste Buchung am')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::created_at)
                    ->label('Erstellt am')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::updated_at)
                    ->label('Zuletzt aktualisiert am')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    private static function formatInterval(FixedCost $record, bool $extended = false): string
    {
        $interval = (string)($extended
            ? $record->{FixedCost::extended_interval}
            : $record->{FixedCost::interval});

        if ($interval !== FixedCostIntervalEnum::CUSTOM->name) {
            return FixedCostIntervalEnum::tryFrom($interval)?->label() ?? $interval;
        }

        $value = $extended
            ? $record->{FixedCost::custom_extended_interval_value}
            : $record->{FixedCost::custom_interval_value};
        $unit = $extended
            ? $record->{FixedCost::custom_extended_interval_unit}
            : $record->{FixedCost::custom_interval_unit};
        $unitLabel = FixedCostIntervalUnitEnum::tryFrom((string)$unit)?->label();

        return $value !== null && $unitLabel !== null
            ? sprintf('Alle %d %s', $value, $unitLabel)
            : FixedCostIntervalEnum::CUSTOM->label();
    }
}

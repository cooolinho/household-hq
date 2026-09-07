<?php

namespace App\Filament\App\Resources\Financial\Insurances\Schemas;

use App\Models\Financial\Insurance;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InsuranceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(Insurance::name),
                TextEntry::make(Insurance::number),
                TextEntry::make(Insurance::belongs_to_category . '.name')
                    ->label('Kategorie')
                    ->placeholder('-'),
                TextEntry::make(Insurance::belongs_to_category . '.group')
                    ->label('Gruppe')
                    ->placeholder('-'),
                TextEntry::make(Insurance::start_date),
                TextEntry::make(Insurance::end_date),
                TextEntry::make(Insurance::company),
                TextEntry::make(Insurance::contact_person),
                TextEntry::make(Insurance::phone),
                TextEntry::make(Insurance::email),
                TextEntry::make(Insurance::address_line_1),
                TextEntry::make(Insurance::address_line_2),
                TextEntry::make(Insurance::address_zip),
                TextEntry::make(Insurance::address_city),
                TextEntry::make(Insurance::address_country),
                TextEntry::make(Insurance::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(Insurance::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

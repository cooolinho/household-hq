<?php

namespace App\Filament\Admin\Resources\Insurances\Schemas;

use App\Models\Insurance;
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
                TextEntry::make(Insurance::type),
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

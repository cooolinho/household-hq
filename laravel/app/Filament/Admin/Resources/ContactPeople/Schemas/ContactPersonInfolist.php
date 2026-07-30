<?php

namespace App\Filament\Admin\Resources\ContactPeople\Schemas;

use App\Models\ContactPerson;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContactPersonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(ContactPerson::title),
                TextEntry::make(ContactPerson::firstname),
                TextEntry::make(ContactPerson::lastname),
                TextEntry::make(ContactPerson::phone_private),
                TextEntry::make(ContactPerson::phone_business),
                TextEntry::make(ContactPerson::email)
                    ->label('Email address'),
                TextEntry::make(ContactPerson::notes)
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make(ContactPerson::avatar)
                    ->placeholder('-'),
                TextEntry::make(ContactPerson::role)
                    ->placeholder('-'),
                TextEntry::make(ContactPerson::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(ContactPerson::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

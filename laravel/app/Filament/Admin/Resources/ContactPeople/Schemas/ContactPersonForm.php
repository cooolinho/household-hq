<?php

namespace App\Filament\Admin\Resources\ContactPeople\Schemas;

use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\ContactPerson;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactPersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(ContactPerson::title)
                    ->required(),
                TextInput::make(ContactPerson::firstname)
                    ->required(),
                TextInput::make(ContactPerson::lastname)
                    ->required(),
                TextInput::make(ContactPerson::phone_private)
                    ->tel(),
                TextInput::make(ContactPerson::phone_business)
                    ->tel(),
                TextInput::make(ContactPerson::email)
                    ->label('Email address')
                    ->email(),
                Textarea::make(ContactPerson::notes)
                    ->columnSpanFull(),
                FileUpload::make(ContactPerson::avatar)
                    ->avatar()
                    ->disk('public')
                    ->placeholder('-'),
                TextInput::make(ContactPerson::role),

                TagResource::getMorphToManySelect($schema, ContactPerson::morph_to_many_tags)
            ]);
    }
}

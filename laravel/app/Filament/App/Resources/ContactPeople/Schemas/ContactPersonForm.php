<?php

namespace App\Filament\App\Resources\ContactPeople\Schemas;

use App\Filament\App\Resources\Tags\TagResource;
use App\Models\ContactPerson;
use App\Models\Enums\ContactPersonTypeEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
                Select::make(ContactPerson::type)
                    ->label('Kontaktart')
                    ->options(ContactPersonTypeEnum::options())
                    ->default(ContactPersonTypeEnum::default())
                    ->required(),

                TagResource::getMorphToManySelect($schema, ContactPerson::morph_to_many_tags)
            ]);
    }
}

<?php

namespace App\Filament\Auth\Pages;

use App\AppConfig;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as FilamentEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends FilamentEditProfile
{

    public function form(Schema $schema): Schema
    {
        $form = parent::form($schema);

        $basisComponents = [
            Section::make('Account Information')
                ->schema($schema->getComponents())
        ];

        $profileComponents = [
            Section::make('Persönliche Informationen')
                ->columnSpanFull()
                ->heading('Persönliche Informationen')
                ->description('Hier können Sie Ihre persönlichen Informationen bearbeiten.')
                ->columns(2)
                ->schema([
                    TextInput::make(User::firstname)
                        ->columnSpan(1),
                    TextInput::make(User::lastname)
                        ->columnSpan(1),
                    TextInput::make(User::date_of_birth)
                        ->columnSpan(1),
                    TextInput::make(User::place_of_birth)
                        ->columnSpan(1),
                    FileUpload::make(User::avatar)
                        ->label('Avatar')
                        ->avatar()
                        ->disk(AppConfig::FILESYSTEM_USER_AVATAR)
                        ->downloadable()
                        ->openable(),
                ]),

            Section::make('Adresse')
                ->columnSpanFull()
                ->heading('Adresse')
                ->description('Hier können Sie Ihre Adresse bearbeiten.')
                ->columns(2)
                ->schema([
                    TextInput::make(User::address_street)
                        ->columnSpan(1),
                    TextInput::make(User::address_street_number)
                        ->columnSpan(1),
                    TextInput::make(User::address_zip)
                        ->columnSpan(1),
                    TextInput::make(User::address_city)
                        ->columnSpan(1),
                ]),

            Section::make('Kontaktinformationen')
                ->columnSpanFull()
                ->heading('Kontaktinformationen')
                ->description('Hier können Sie Ihre Kontaktinformationen bearbeiten.')
                ->schema([
                    TextInput::make(User::phone),
                    TextInput::make(User::email_business)->email(),
                    TextInput::make(User::email_private)->email(),
                ]),

        ];

        return $form->components(array_merge($basisComponents, $profileComponents));
    }
}

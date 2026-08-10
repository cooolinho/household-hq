<?php

namespace App\Filament\Auth\Pages;

use App\AppConfig;
use App\Models\ImapAccount;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as FilamentEditProfile;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

            Section::make('IMAP Einstellungen')
                ->columnSpanFull()
                ->heading('IMAP Einstellungen')
                ->description('Verwalten Sie mehrere Mailboxen für den automatischen Dokumentimport.')
                ->schema([
                    Repeater::make(User::has_many_imap_accounts)
                        ->relationship(User::has_many_imap_accounts)
                        ->label('IMAP Konten')
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state[ImapAccount::name] ?? null)
                        ->columns(2)
                        ->schema([
                            TextInput::make(ImapAccount::name)
                                ->label('Kontoname')
                                ->required(),
                            Toggle::make(ImapAccount::is_active)
                                ->label('Aktiv')
                                ->default(true),
                            TextInput::make(ImapAccount::host)
                                ->label('Host')
                                ->required(),
                            TextInput::make(ImapAccount::port)
                                ->label('Port')
                                ->numeric()
                                ->default(993)
                                ->required(),
                            Select::make(ImapAccount::encryption)
                                ->label('Verschlüsselung')
                                ->options([
                                    'ssl' => 'SSL',
                                    'tls' => 'TLS',
                                    'none' => 'Keine',
                                ])
                                ->default('ssl')
                                ->required(),
                            TextInput::make(ImapAccount::username)
                                ->label('Benutzername')
                                ->required(),
                            TextInput::make(ImapAccount::password)
                                ->label('Passwort')
                                ->password()
                                ->revealable()
                                ->required(),
                            TextInput::make(ImapAccount::inbox_folder)
                                ->label('Inbox Ordner')
                                ->default('INBOX')
                                ->required(),
                            TextInput::make(ImapAccount::processed_folder)
                                ->label('Processed Ordner')
                                ->default('Processed')
                                ->required(),
                            Toggle::make(ImapAccount::mark_as_read)
                                ->label('Nach Import als gelesen markieren')
                                ->default(true),
                            TagsInput::make(ImapAccount::blacklisted_senders)
                                ->label('Blockierte Absender')
                                ->placeholder('newsletter@example.com')
                                ->helperText('Eine E-Mail-Adresse oder Teilzeichenfolge pro Tag. Groß-/Kleinschreibung wird ignoriert.')
                                ->columnSpanFull(),
                            TagsInput::make(ImapAccount::blacklisted_subject_keywords)
                                ->label('Blockierte Schlagwörter im Betreff')
                                ->placeholder('Werbung')
                                ->helperText('Ein Schlagwort pro Tag. Groß-/Kleinschreibung wird ignoriert.')
                                ->columnSpanFull(),
                            CheckboxList::make(ImapAccount::allowed_extensions)
                                ->label('Erlaubte Dateitypen')
                                ->options([
                                    'pdf' => 'PDF',
                                    'txt' => 'TXT',
                                    'docx' => 'DOCX',
                                ])
                                ->default(['pdf', 'txt', 'docx'])
                                ->columns(3)
                                ->required(),
                        ]),
                ]),

        ];

        return $form->components(array_merge($basisComponents, $profileComponents));
    }
}

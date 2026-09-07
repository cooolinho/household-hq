<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    /**
     * Kein Modell-Feld - steuert nur, ob nach dem Anlegen sofort
     * email_verified_at gesetzt wird (siehe CreateUser::mutateFormDataBeforeCreate).
     */
    public const string FIELD_MARK_EMAIL_VERIFIED = 'mark_email_verified';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(User::name)
                    ->label(__('admin.resource.user.fields.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make(User::email)
                    ->label(__('admin.resource.user.fields.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make(User::password)
                    ->label(__('admin.resource.user.fields.password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'create'
                        ? null
                        : 'Leer lassen, um das aktuelle Passwort zu behalten.')
                    ->maxLength(255),

                Select::make(User::role)
                    ->label(__('admin.resource.user.fields.role'))
                    ->options(Role::class)
                    ->required()
                    ->native(false)
                    ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false)
                    ->helperText(fn (?User $record): ?string => ($record?->is(Auth::user()) ?? false)
                        ? 'Die eigene Rolle kann nicht geändert werden.'
                        : null),

                Toggle::make(User::is_active)
                    ->label(__('admin.resource.user.fields.is_active'))
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false)
                    ->helperText(fn (?User $record): ?string => ($record?->is(Auth::user()) ?? false)
                        ? 'Der eigene Account kann nicht gesperrt werden.'
                        : null),

                Toggle::make(self::FIELD_MARK_EMAIL_VERIFIED)
                    ->label(__('admin.resource.user.fields.mark_email_verified'))
                    ->default(true)
                    ->dehydrated()
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Ist der Schalter aus, erhält der Benutzer nach dem Anlegen eine Verifizierungs-E-Mail.'),
            ]);
    }
}

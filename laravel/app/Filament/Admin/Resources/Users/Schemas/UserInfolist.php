<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(User::name)
                    ->label(__('admin.resource.user.fields.name')),
                TextEntry::make(User::email)
                    ->label(__('admin.resource.user.fields.email'))
                    ->copyable(),
                TextEntry::make(User::role)
                    ->label(__('admin.resource.user.fields.role'))
                    ->badge(),
                IconEntry::make(User::is_active)
                    ->label(__('admin.resource.user.fields.is_active'))
                    ->boolean(),
                IconEntry::make(User::email_verified_at)
                    ->label(__('admin.resource.user.fields.email_verified_at'))
                    ->boolean()
                    ->state(fn (User $record): bool => $record->hasVerifiedEmail()),
                TextEntry::make(User::created_at)
                    ->label(__('admin.resource.user.fields.created_at'))
                    ->dateTime('d.m.Y H:i'),
            ]);
    }
}

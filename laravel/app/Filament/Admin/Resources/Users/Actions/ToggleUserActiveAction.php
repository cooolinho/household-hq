<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ToggleUserActiveAction
{
    public static function make(): Action
    {
        return Action::make('toggleActive')
            ->label(fn (User $record): string => $record->is_active ? 'Sperren' : 'Entsperren')
            ->icon(fn (User $record): string|BackedEnum => $record->is_active
                ? Heroicon::OutlinedLockClosed
                : Heroicon::OutlinedLockOpen)
            ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->modalDescription(fn (User $record): string => $record->is_active
                ? 'Der Benutzer kann sich danach nicht mehr anmelden und wird bei einer bestehenden Sitzung mit dem nächsten Aufruf abgemeldet.'
                : 'Der Benutzer kann sich danach wieder anmelden.')
            // Selbstschutz: niemand kann sich selbst aussperren.
            ->visible(fn (User $record): bool => $record->isNot(Auth::user()))
            ->action(function (User $record): void {
                $record->update([
                    User::is_active => !$record->is_active,
                ]);

                Notification::make()
                    ->title($record->is_active ? 'Benutzer wurde entsperrt.' : 'Benutzer wurde gesperrt.')
                    ->success()
                    ->send();
            });
    }
}

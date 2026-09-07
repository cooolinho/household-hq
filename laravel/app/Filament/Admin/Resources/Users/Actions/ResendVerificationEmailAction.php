<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\RateLimiter;

class ResendVerificationEmailAction
{
    private const int MAX_ATTEMPTS = 2;

    private const int DECAY_SECONDS = 60;

    public static function make(): Action
    {
        return Action::make('resendVerificationEmail')
            ->label('Verifizierung erneut senden')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (User $record): bool => !$record->hasVerifiedEmail())
            ->action(function (User $record): void {
                $rateLimitingKey = 'admin-resend-email-verification:' . $record->getKey();

                if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: self::MAX_ATTEMPTS)) {
                    Notification::make()
                        ->title('Bitte kurz warten, bevor die E-Mail erneut gesendet wird.')
                        ->warning()
                        ->send();

                    return;
                }

                RateLimiter::hit($rateLimitingKey, decaySeconds: self::DECAY_SECONDS);

                self::send($record);

                Notification::make()
                    ->title('Verifizierungs-E-Mail wurde erneut versendet.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Baut und verschickt dieselbe Notification, die Filament auch beim
     * regulären Verifizierungs-Prompt verwendet, siehe
     * Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt::sendEmailVerificationNotification().
     *
     * WICHTIG: Filament::getVerifyEmailUrl() nutzt intern das aktuell aktive
     * Panel. In einer Admin-Panel-Action ist das "admin" - das Panel hat
     * keine E-Mail-Verifizierung, die Route existiert dort nicht und der
     * Aufruf würde mit einer RouteNotFoundException sterben. Deshalb wird
     * hier explizit das App-Panel adressiert.
     */
    public static function send(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $notification = app(VerifyEmail::class);
        $notification->url = Filament::getPanel('app')->getVerifyEmailUrl($user);

        $user->notify($notification);
    }
}

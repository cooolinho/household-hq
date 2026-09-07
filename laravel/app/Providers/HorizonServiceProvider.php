<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Konfiguriert die Horizon-Autorisierung.
     *
     * Überschreibt die Basisimplementierung bewusst komplett (nicht nur
     * gate()): Laravel's Standard-Verhalten lässt in APP_ENV=local JEDEN
     * Benutzer - auch Gäste - unabhängig vom Gate auf Horizon zu
     * ("Gate::check(...) || app()->environment('local')"). Da dieses Projekt
     * lokal per Docker mit APP_ENV=local läuft, würde das die Anforderung
     * "nur Admins" faktisch aushebeln.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(fn ($request) => Gate::check('viewHorizon', [$request->user()]));
    }

    /**
     * Register the Horizon gate.
     *
     * Nur aktive Admins dürfen das Horizon-Dashboard sehen.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user): bool {
            return ($user?->is_active && $user->isAdmin()) ?? false;
        });
    }
}

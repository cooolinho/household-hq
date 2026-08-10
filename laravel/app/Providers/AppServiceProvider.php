<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            Log::channel('database')->info('Benutzer hat sich eingeloggt.', [
                'event' => 'auth.login',
                'user_id' => $event->user->getAuthIdentifier(),
                'guard' => $event->guard,
            ]);
        });

        Queue::after(function (JobProcessed $event): void {
            Log::channel('database')->info('Queue-Job wurde erfolgreich verarbeitet.', [
                'event' => 'job.completed',
                'job_name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
            ]);
        });
    }
}

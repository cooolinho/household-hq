<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\EditProfile;
use App\Filament\Auth\Pages\Login;
use App\Filament\AvatarProviders\UserAvatarProvider;
use App\Menu\NavigationGroup;
use Filament\Auth\Pages\Register;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()

            // base
            ->id('app')
            ->path('app')

            // theme
            ->viteTheme('resources/scss/filament/app/theme.scss')
            ->colors($this->getColors())
            ->darkMode()

            // auth
            ->login(Login::class)
            ->registration(config('auth.registration.enabled') ? Register::class : null)
            ->emailVerification()
            ->passwordReset()
            ->defaultAvatarProvider(UserAvatarProvider::class)
            ->profile(EditProfile::class, false)

            // navigation
            ->navigationGroups(NavigationGroup::class)
            ->userMenuItems($this->getUserMenuItems())
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()

            // Auto Discover
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->discoverClusters(in: app_path('Filament/App/Clusters'), for: 'App\Filament\App\Clusters')

            // manually register
            ->pages($this->getPages())
            ->widgets($this->getWidgets())
            ->middleware($this->getMiddleware())
            ->authMiddleware($this->getAuthMiddleware())

            // Database Notifications
            // https://filamentphp.com/docs/5.x/notifications/database-notifications
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->databaseNotifications(position: DatabaseNotificationsPosition::Topbar);
    }

    public function boot(): void
    {
        if (config('app.secure', false)) {
            URL::useOrigin(config('app.url'));
            URL::forceScheme('https');

            // Zusätzlich für Docker/Proxy Umgebungen:
            if (request()->server->has('HTTP_X_FORWARDED_PROTO')) {
                request()->server->set('HTTPS', 'on');
            }
        }
    }

    /**
     * @link https://filamentphp.com/docs/5.x/navigation/user-menu
     *
     * @return array
     */
    private function getUserMenuItems(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    private function getWidgets(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    private function getMiddleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];
    }

    /**
     * @return string[]
     */
    private function getAuthMiddleware(): array
    {
        return [
            Authenticate::class,
        ];
    }

    /**
     * @return string[]
     */
    private function getPages(): array
    {
        return [];
    }

    /**
     * @return array
     */
    private function getColors(): array
    {
        return [
            'primary' => Color::Amber,
        ];
    }
}

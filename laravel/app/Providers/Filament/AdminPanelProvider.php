<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\UserAvatarProvider;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // base
            ->id('admin')
            ->path('admin')

            // theme
            ->viteTheme('resources/scss/filament/admin/theme.scss')
            ->colors($this->getColors())
            ->darkMode()

            // auth
            // Kein ->login(), ->registration() oder ->emailVerification() hier:
            // Das Admin-Panel hat keine eigene Login-Seite. Nicht eingeloggte
            // Zugriffe werden über die zentrale /login-Route zum App-Panel
            // geleitet (siehe routes/web.php), Registrierung gibt es hier nicht.
            ->defaultAvatarProvider(UserAvatarProvider::class)

            // navigation
            ->navigationItems($this->getNavigationItems())
            ->userMenuItems($this->getUserMenuItems())
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()

            // Auto Discover
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->discoverClusters(in: app_path('Filament/Admin/Clusters'), for: 'App\Filament\Admin\Clusters')

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
     * @return array<NavigationItem>
     */
    private function getNavigationItems(): array
    {
        return [
            NavigationItem::make('Horizon')
                ->url(fn () => url(config('horizon.path')), shouldOpenInNewTab: true)
                ->icon(Heroicon::OutlinedQueueList)
                ->sort(10),
        ];
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
     * @return array
     */
    private function getPages(): array
    {
        return [
            Dashboard::class,
        ];
    }

    /**
     * @return array
     */
    private function getColors(): array
    {
        return [
            'primary' => Color::Red,
        ];
    }
}

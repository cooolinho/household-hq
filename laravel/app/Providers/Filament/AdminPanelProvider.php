<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Actions\Action;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()

            // base
            ->id('admin')
            ->path('admin')

            // theme
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors($this->getColors())

            // auth
            ->login()
            ->passwordReset()
            ->profile()

            // navigation
            ->navigationGroups($this->getNavigationGroups())
            ->userMenuItems($this->getUserMenuItems())

            // Auto Discover
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')

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
     * @return array
     */
    private function getNavigationGroups(): array
    {
        return [];
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
            'primary' => Color::Amber,
        ];
    }
}

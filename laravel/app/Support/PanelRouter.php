<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Ermittelt anhand der Rolle eines Benutzers das Ziel-Panel. Zentrale Stelle
 * für die Login-Weiterleitung (App\Http\Responses\LoginResponse) und die
 * Root-Route (routes/web.php), damit beide dieselbe Logik verwenden.
 */
class PanelRouter
{
    public static function panelId(?Authenticatable $user): string
    {
        /** @var User|null $user */
        return $user?->isAdmin() ? 'admin' : 'app';
    }

    public static function urlFor(?Authenticatable $user): string
    {
        return Filament::getPanel(self::panelId($user))->getUrl() ?? '/';
    }
}

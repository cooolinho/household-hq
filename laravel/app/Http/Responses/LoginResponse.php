<?php

namespace App\Http\Responses;

use App\Support\PanelRouter;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Ersetzt Filaments Standard-LoginResponse (die immer ins zuletzt aktive
 * Panel zurückführt) durch eine Weiterleitung anhand der Benutzerrolle:
 * ROLE_ADMIN -> Admin-Panel, alle anderen (ROLE_USER) -> App-Panel.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $panel = Filament::getPanel(PanelRouter::panelId(Auth::user()));
        $default = $panel->getUrl() ?? '/';

        $this->forgetIntendedUrlIfOutsidePanel($panel->getPath());

        return redirect()->intended($default);
    }

    /**
     * `redirect()->intended()` würde sonst eine vor dem Login gemerkte URL
     * aus einem anderen Panel übernehmen (z. B. ein ROLE_USER, der zuvor auf
     * /admin/... umgeleitet wurde) und liefe dort sofort in ein 403.
     */
    private function forgetIntendedUrlIfOutsidePanel(string $panelPath): void
    {
        $intended = session('url.intended');

        if (!is_string($intended)) {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?? '';
        $prefix = '/' . $panelPath;

        if ($path === $prefix || Str::startsWith($path, $prefix . '/')) {
            return;
        }

        session()->forget('url.intended');
    }
}

<?php

namespace App\Filament\AvatarProviders;

use App\Models\User;
use Filament\AvatarProviders\Contracts;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class UserAvatarProvider implements Contracts\AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        if ($record instanceof User && $avatarUrl = $record->getAvatarUrl()) {
            return $avatarUrl;
        }

        return self::getFallbackAvatarUrl();
    }

    public static function getFallbackAvatarUrl(): string
    {
        return asset('images/avatar.jpg');
    }
}

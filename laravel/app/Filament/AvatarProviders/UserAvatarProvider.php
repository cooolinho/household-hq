<?php

namespace App\Filament\AvatarProviders;

use App\AppConfig;
use App\Models\User;
use Filament\AvatarProviders\Contracts;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserAvatarProvider implements Contracts\AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        if ($record instanceof User && !empty($record->avatar)) {
            return $this->getAvatarUrlForUser($record);
        }

        return $this->getDefaultAvatar();
    }

    private function getAvatarUrlForUser(User $user): string
    {
        $disk = Storage::disk(AppConfig::FILESYSTEM_USER_AVATAR);
        if (!$disk->exists($user->avatar)) {
            return $this->getDefaultAvatar();
        }

        return $disk->url($user->avatar);
    }

    private function getDefaultAvatar(): string
    {
        return asset('images/avatar.jpg');
    }
}

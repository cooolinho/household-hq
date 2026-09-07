<?php

namespace App\Filament\Auth\Pages;

use App\Models\User;
use Filament\Auth\Pages\Login as FilamentLogin;

class Login extends FilamentLogin
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            ...parent::getCredentialsFromFormData($data),
            User::is_active => true,
        ];
    }
}

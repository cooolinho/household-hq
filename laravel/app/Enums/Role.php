<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => __('Benutzer'),
            self::ADMIN => __('Administrator'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::USER => 'gray',
            self::ADMIN => 'danger',
        };
    }
}

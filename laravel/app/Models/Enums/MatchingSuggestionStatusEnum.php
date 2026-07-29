<?php

namespace App\Models\Enums;

enum MatchingSuggestionStatusEnum
{
    use UseEnumOptionsTrait;

    case PENDING;
    case ACCEPTED;
    case REJECTED;

    public static function default(): string
    {
        return self::PENDING->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::ACCEPTED => 'Akzeptiert',
            self::REJECTED => 'Abgelehnt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }
}


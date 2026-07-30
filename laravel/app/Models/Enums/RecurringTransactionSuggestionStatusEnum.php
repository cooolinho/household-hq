<?php

namespace App\Models\Enums;

enum RecurringTransactionSuggestionStatusEnum
{
    use UseEnumOptionsTrait;

    case PENDING;
    case ACCEPTED;
    case DISMISSED;

    public static function default(): string
    {
        return self::PENDING->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::ACCEPTED => 'Akzeptiert',
            self::DISMISSED => 'Ausgeblendet',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACCEPTED => 'success',
            self::DISMISSED => 'gray',
        };
    }
}


<?php

namespace App\Models\Enums;

enum BankAccountTypeEnum
{
    use UseEnumOptionsTrait;

    case GIRO;
    case CASH;
    case SAVINGS;

    public function label(): string
    {
        return match ($this) {
            self::GIRO => 'Giro',
            self::CASH => 'Cash',
            self::SAVINGS => 'Savings',
        };
    }
}

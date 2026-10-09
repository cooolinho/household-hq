<?php

namespace App\Services\TransactionImport;

/**
 * Zieltyp eines importierten Wertes, abgeleitet aus den Casts des Transaction-Models.
 */
enum ValueType
{
    case Date;
    case Decimal;
    case Boolean;
    case Enum;
    case Text;

    public static function fromCast(?string $cast): self
    {
        if ($cast === null) {
            return self::Text;
        }

        $base = strtolower(explode(':', $cast, 2)[0]);

        return match (true) {
            in_array($base, ['date', 'datetime', 'immutable_date', 'immutable_datetime'], true) => self::Date,
            in_array($base, ['float', 'double', 'real', 'decimal', 'int', 'integer'], true) => self::Decimal,
            in_array($base, ['bool', 'boolean'], true) => self::Boolean,
            enum_exists($cast) => self::Enum,
            default => self::Text,
        };
    }
}

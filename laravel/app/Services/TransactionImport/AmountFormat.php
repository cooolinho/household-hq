<?php

namespace App\Services\TransactionImport;

/**
 * Zahlenformat der Beträge in der CSV-Datei (CSVImportProfile::amount_format).
 */
enum AmountFormat: string
{
    case German = 'de_de';
    case English = 'en_us';
    case Auto = 'auto';

    public function label(): string
    {
        return match ($this) {
            self::German => 'Deutsch (1.890,70)',
            self::English => 'Englisch (1,890.70)',
            self::Auto => 'Automatisch erkennen',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

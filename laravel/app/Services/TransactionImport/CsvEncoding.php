<?php

namespace App\Services\TransactionImport;

/**
 * Zeichenkodierung der CSV-Datei (CSVImportProfile::encoding).
 */
enum CsvEncoding: string
{
    /** UTF-8, ungültige UTF-8-Werte werden als Windows-1252 interpretiert (typisch für ältere Bankexporte). */
    case Auto = 'auto';
    case Utf8 = 'UTF-8';
    case Windows1252 = 'Windows-1252';
    case Iso88591 = 'ISO-8859-1';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Automatisch (UTF-8 / Windows-1252)',
            self::Utf8 => 'UTF-8',
            self::Windows1252 => 'Windows-1252',
            self::Iso88591 => 'ISO-8859-1 (Latin-1)',
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

    /**
     * Wandelt einen Rohwert aus der Datei nach UTF-8 um. Ungültige Bytes werden ersetzt,
     * damit keine kaputten Zeichenketten in Datenbank oder Livewire-State landen.
     */
    public function toUtf8(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $converted = match ($this) {
            self::Utf8 => $value,
            self::Auto => mb_check_encoding($value, 'UTF-8')
                ? $value
                : mb_convert_encoding($value, 'UTF-8', 'Windows-1252'),
            default => mb_convert_encoding($value, 'UTF-8', $this->value),
        };

        return mb_scrub($converted, 'UTF-8');
    }
}

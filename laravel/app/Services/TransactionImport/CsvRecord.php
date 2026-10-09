<?php

namespace App\Services\TransactionImport;

/**
 * Ein Datensatz der CSV-Datei inklusive der Zeilennummer, in der er beginnt (1-basiert, wie im Editor).
 */
final readonly class CsvRecord
{
    /**
     * @param  list<string>  $values  bereits nach UTF-8 konvertierte Rohwerte
     */
    public function __construct(
        public int $line,
        public array $values,
    ) {}

    public function isEmpty(): bool
    {
        foreach ($this->values as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }

    public function value(int $index): ?string
    {
        return $this->values[$index] ?? null;
    }

    public function hasColumn(int $index): bool
    {
        return array_key_exists($index, $this->values);
    }
}

<?php

namespace App\Services\TransactionImport;

use App\Exceptions\TransactionsImportException;
use Generator;

/**
 * Liest CSV-Dateien zeilenweise (streamend) ein. Werte werden ausschließlich als Text behandelt und nie ausgewertet.
 */
class CsvReader
{
    private const string UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * Spaltennamen der Header-Zeile (leere Namen werden durch "Spalte N" ersetzt).
     * Ohne Header-Zeile wird ein leeres Array geliefert.
     *
     * @return list<string>
     *
     * @throws TransactionsImportException
     */
    public function header(string $path, CsvFormat $format): array
    {
        if (! $format->hasHeader) {
            return [];
        }

        foreach ($this->physicalRecords($path, $format) as $index => $record) {
            if ($index < $format->headerOffset) {
                continue;
            }

            $header = [];
            foreach ($record->values as $column => $name) {
                $name = trim($name);
                $header[] = $name === '' ? self::columnLabel($column) : $name;
            }

            return $record->isEmpty() ? [] : $header;
        }

        return [];
    }

    /**
     * Alle Datensätze nach der Präambel (header offset) und der Header-Zeile, inkl. leerer Zeilen.
     *
     * @return Generator<int, CsvRecord>
     *
     * @throws TransactionsImportException
     */
    public function records(string $path, CsvFormat $format): Generator
    {
        $firstDataRecord = $format->headerOffset + ($format->hasHeader ? 1 : 0);

        foreach ($this->physicalRecords($path, $format) as $index => $record) {
            if ($index >= $firstDataRecord) {
                yield $record;
            }
        }
    }

    public static function columnLabel(int $index): string
    {
        return 'Spalte '.($index + 1);
    }

    /**
     * @return Generator<int, CsvRecord>
     *
     * @throws TransactionsImportException
     */
    private function physicalRecords(string $path, CsvFormat $format): Generator
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new TransactionsImportException('Die CSV-Datei konnte nicht gelesen werden.');
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new TransactionsImportException('Die CSV-Datei konnte nicht geöffnet werden.');
        }

        try {
            $line = 1;
            $index = 0;

            while (($fields = fgetcsv($handle, null, $format->delimiter, $format->enclosure, $format->escape)) !== false) {
                // Leerzeilen liefert fgetcsv als [null]
                $fields = array_map(fn ($value) => (string) $value, $fields);

                if ($index === 0 && isset($fields[0]) && str_starts_with($fields[0], self::UTF8_BOM)) {
                    $fields[0] = substr($fields[0], strlen(self::UTF8_BOM));
                }

                $consumedLines = 1;
                $values = [];

                foreach ($fields as $value) {
                    $consumedLines += substr_count($value, "\n");
                    $values[] = $this->sanitize($format->encoding->toUtf8($value));
                }

                yield $index => new CsvRecord($line, $values);

                $line += $consumedLines;
                $index++;
            }
        } finally {
            fclose($handle);
        }
    }

    /** Entfernt NUL-Bytes und sonstige Steuerzeichen (Tab und Zeilenumbrüche bleiben erhalten). */
    private function sanitize(string $value): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    }
}

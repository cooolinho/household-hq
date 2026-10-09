<?php

namespace App\Services\TransactionImport;

use App\Models\Financial\CSVImportProfile;

/**
 * Löst das Mapping eines Import-Profils gegen die Spalten einer konkreten CSV-Datei auf
 * und wandelt ein im Wizard bearbeitetes Mapping zurück in Profil-Attribute.
 *
 * Profile speichern das Mapping doppelt: über den Spaltennamen (header_mapping, bevorzugt) und über den
 * Spaltenindex (mapping). Der Index dient als Fallback für Dateien ohne Header-Zeile, für ältere Profile
 * und zur Auflösung doppelter Spaltennamen (z. B. zweimal "Währung" im ING-Export).
 */
class MappingResolver
{
    public function resolve(?CSVImportProfile $profile, CsvAnalysis $analysis): ResolvedMapping
    {
        if ($profile === null) {
            return $this->suggest($analysis);
        }

        $headerMapping = array_filter(
            $profile->header_mapping ?? [],
            fn ($name) => is_string($name) && trim($name) !== '',
        );

        if ($analysis->hasHeader && $headerMapping !== []) {
            return $this->resolveByHeader($profile, $headerMapping, $analysis);
        }

        return $this->resolveByIndex($profile, $analysis);
    }

    /**
     * Mapping-Vorschlag ohne Profil anhand typischer Spaltennamen (TransactionImportField::aliases()).
     */
    public function suggest(CsvAnalysis $analysis): ResolvedMapping
    {
        $columns = array_fill_keys(TransactionImportField::values(), null);

        if (! $analysis->hasHeader) {
            return new ResolvedMapping($columns, ResolvedMapping::SOURCE_SUGGESTION);
        }

        $header = array_map($this->normalizeName(...), $analysis->header);
        $taken = [];

        foreach (TransactionImportField::cases() as $field) {
            foreach ($field->aliases() as $alias) {
                foreach ($header as $index => $name) {
                    if ($name === $alias && ! isset($taken[$index])) {
                        $columns[$field->value] = $index;
                        $taken[$index] = true;

                        continue 3;
                    }
                }
            }
        }

        return new ResolvedMapping($columns, ResolvedMapping::SOURCE_SUGGESTION);
    }

    /**
     * Bereinigt ein (vom Benutzer bearbeitetes) Mapping: nur bekannte Felder, nur existierende Spaltenindizes.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, int|null>
     */
    public function sanitize(array $input, int $columnCount): array
    {
        $columns = [];

        foreach (TransactionImportField::values() as $field) {
            $index = $this->toIndex($input[$field] ?? null);
            $columns[$field] = $index !== null && $index < $columnCount ? $index : null;
        }

        return $columns;
    }

    /**
     * Profil-Attribute für ein Mapping, inklusive Spaltennamen für die spätere automatische Zuordnung.
     *
     * @param  array<string, int|null>  $columns
     * @return array<string, mixed>
     */
    public function toProfileAttributes(CsvFormat $format, array $columns, CsvAnalysis $analysis): array
    {
        $mapping = [];
        $headerMapping = [];

        foreach (TransactionImportField::values() as $field) {
            $index = $columns[$field] ?? null;
            $mapping[$field] = $index;
            $headerMapping[$field] = $index !== null && $analysis->hasHeader ? ($analysis->header[$index] ?? null) : null;
        }

        return [
            ...$format->toArray(),
            CSVImportProfile::mapping => $mapping,
            CSVImportProfile::header_mapping => $analysis->hasHeader ? $headerMapping : null,
            CSVImportProfile::header_columns => $analysis->hasHeader ? $analysis->header : null,
        ];
    }

    /**
     * @param  array<string, string>  $headerMapping
     */
    private function resolveByHeader(CSVImportProfile $profile, array $headerMapping, CsvAnalysis $analysis): ResolvedMapping
    {
        $header = array_map($this->normalizeName(...), $analysis->header);
        $columns = array_fill_keys(TransactionImportField::values(), null);
        $missing = [];
        $ambiguous = [];

        foreach (TransactionImportField::values() as $field) {
            $expected = $headerMapping[$field] ?? null;

            if ($expected === null) {
                continue;
            }

            $positions = array_keys($header, $this->normalizeName($expected), true);

            if (count($positions) === 1) {
                $columns[$field] = $positions[0];
            } elseif ($positions === []) {
                $missing[$field] = $expected;
            } else {
                $storedIndex = $this->toIndex($profile->mapping[$field] ?? null);

                if ($storedIndex !== null && in_array($storedIndex, $positions, true)) {
                    $columns[$field] = $storedIndex;
                } else {
                    $ambiguous[$field] = $expected;
                }
            }
        }

        $knownColumns = array_map($this->normalizeName(...), $profile->header_columns ?? []);
        $newColumns = $knownColumns === [] ? [] : array_values(array_filter(
            $analysis->header,
            fn (string $name) => ! in_array($this->normalizeName($name), $knownColumns, true),
        ));

        return new ResolvedMapping($columns, ResolvedMapping::SOURCE_PROFILE_HEADER, $missing, $ambiguous, $newColumns);
    }

    private function resolveByIndex(CSVImportProfile $profile, CsvAnalysis $analysis): ResolvedMapping
    {
        $columns = array_fill_keys(TransactionImportField::values(), null);
        $missing = [];

        foreach (TransactionImportField::values() as $field) {
            $index = $this->toIndex($profile->mapping[$field] ?? null);

            if ($index === null) {
                continue;
            }

            if ($index < $analysis->columnCount) {
                $columns[$field] = $index;
            } else {
                $missing[$field] = CsvReader::columnLabel($index);
            }
        }

        return new ResolvedMapping($columns, ResolvedMapping::SOURCE_PROFILE_INDEX, $missing);
    }

    private function toIndex(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (is_string($value) && preg_match('/^\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        return null;
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)) ?? '');
    }
}

<?php

namespace App\Services\TransactionImport;

/**
 * Ergebnis der Zuordnung CSV-Spalten → Transaction-Felder für eine konkrete CSV-Datei.
 */
final readonly class ResolvedMapping
{
    public const string SOURCE_PROFILE_HEADER = 'profile_header';

    public const string SOURCE_PROFILE_INDEX = 'profile_index';

    public const string SOURCE_SUGGESTION = 'suggestion';

    /**
     * @param  array<string, int|null>  $columns  Transaction-Feld => 0-basierter Spaltenindex (null = nicht importieren)
     * @param  array<string, string>  $missingColumns  Feld => erwartete Spalte, die in der CSV fehlt
     * @param  array<string, string>  $ambiguousColumns  Feld => Spaltenname, der mehrfach vorkommt und nicht eindeutig ist
     * @param  list<string>  $newColumns  Spalten der CSV, die beim Speichern des Profils nicht vorhanden waren
     */
    public function __construct(
        public array $columns,
        public string $source,
        public array $missingColumns = [],
        public array $ambiguousColumns = [],
        public array $newColumns = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'columns' => $this->columns,
            'source' => $this->source,
            'missing_columns' => $this->missingColumns,
            'ambiguous_columns' => $this->ambiguousColumns,
            'new_columns' => $this->newColumns,
        ];
    }
}

<?php

namespace App\Services\TransactionImport;

/**
 * Zusammenfassung einer Prüfung (Dry-Run) bzw. eines durchgeführten Imports.
 * Zeilennummern und Fehlermeldungen werden begrenzt gespeichert, die Zähler sind immer vollständig.
 */
final class ImportReport
{
    public const int MAX_LISTED_LINES = 50;

    public const int MAX_LISTED_ERRORS = 200;

    public int $records = 0;

    public int $emptyLines = 0;

    public int $valid = 0;

    public int $imported = 0;

    public int $invalid = 0;

    public int $duplicatesInDatabase = 0;

    public int $duplicatesInFile = 0;

    public bool $executed = false;

    /** @var list<string> */
    public array $mappingErrors = [];

    /** @var array<string, array{count: int, lines: list<int>}> ImportProblem-Wert => betroffene Datensätze */
    public array $problems = [];

    /** @var list<array{line: int, message: string}> */
    public array $errors = [];

    /** @var list<int> */
    public array $duplicateLines = [];

    /**
     * @param  list<array{field: string, problem: ImportProblem, message: string}>  $errors
     */
    public function addInvalidRow(int $line, array $errors): void
    {
        $this->invalid++;

        $problems = [];
        foreach ($errors as $error) {
            $problems[$error['problem']->value] = true;

            if (count($this->errors) < self::MAX_LISTED_ERRORS) {
                $this->errors[] = ['line' => $line, 'message' => $error['message']];
            }
        }

        foreach (array_keys($problems) as $problem) {
            $this->problems[$problem] ??= ['count' => 0, 'lines' => []];
            $this->problems[$problem]['count']++;

            if (count($this->problems[$problem]['lines']) < self::MAX_LISTED_LINES) {
                $this->problems[$problem]['lines'][] = $line;
            }
        }
    }

    public function addDuplicate(int $line, bool $inFile): void
    {
        $inFile ? $this->duplicatesInFile++ : $this->duplicatesInDatabase++;

        if (count($this->duplicateLines) < self::MAX_LISTED_LINES) {
            $this->duplicateLines[] = $line;
        }
    }

    public function duplicates(): int
    {
        return $this->duplicatesInDatabase + $this->duplicatesInFile;
    }

    /** Übersprungene Datensätze: Duplikate und (falls erlaubt) fehlerhafte Datensätze. */
    public function skipped(): int
    {
        return $this->duplicates() + ($this->executed ? $this->invalid : 0);
    }

    public function hasBlockingErrors(bool $skipInvalidRows): bool
    {
        return $this->mappingErrors !== [] || (! $skipInvalidRows && $this->invalid > 0);
    }

    public function canImport(bool $skipInvalidRows): bool
    {
        return ! $this->hasBlockingErrors($skipInvalidRows) && $this->valid > 0;
    }

    /** @return list<array{summary: string, lines: list<int>, more: int}> */
    public function problemSummaries(): array
    {
        $summaries = [];

        foreach ($this->problems as $problem => $data) {
            $summaries[] = [
                'summary' => ImportProblem::from($problem)->summary($data['count']),
                'lines' => $data['lines'],
                'more' => max(0, $data['count'] - count($data['lines'])),
            ];
        }

        return $summaries;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $report = new self;

        foreach (get_object_vars($report) as $property => $default) {
            if (array_key_exists($property, $data)) {
                $report->{$property} = $data[$property];
            }
        }

        return $report;
    }
}

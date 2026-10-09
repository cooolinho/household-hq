<?php

namespace App\Services\TransactionImport;

/**
 * Ergebnis der Analyse einer CSV-Datei: erkannte Spalten, Anzahl Datensätze und Beispielzeilen.
 */
final readonly class CsvAnalysis
{
    /**
     * @param  list<string>  $header  Spaltennamen (ohne Header-Zeile: "Spalte 1", "Spalte 2", ...)
     * @param  list<CsvRecord>  $previewRecords
     * @param  list<int>  $irregularLines  Zeilen, deren Spaltenanzahl von der Header-Zeile abweicht (gekürzt)
     */
    public function __construct(
        public array $header,
        public bool $hasHeader,
        public int $columnCount,
        public int $recordCount,
        public int $emptyLineCount,
        public array $previewRecords,
        public int $irregularRecordCount,
        public array $irregularLines,
    ) {}

    /** @return array<int, string> Spaltenindex => "Sp. 1 · Buchungstag" für Auswahlfelder */
    public function columnOptions(): array
    {
        $options = [];

        foreach ($this->header as $index => $name) {
            $label = 'Sp. '.($index + 1);
            $options[$index] = $this->hasHeader ? $label.' · '.$name : $label;
        }

        return $options;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'header' => $this->header,
            'has_header' => $this->hasHeader,
            'column_count' => $this->columnCount,
            'record_count' => $this->recordCount,
            'empty_line_count' => $this->emptyLineCount,
            'preview' => array_map(
                fn (CsvRecord $record) => ['line' => $record->line, 'values' => $record->values],
                $this->previewRecords,
            ),
            'irregular_record_count' => $this->irregularRecordCount,
            'irregular_lines' => $this->irregularLines,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            header: array_values(array_map('strval', $data['header'] ?? [])),
            hasHeader: (bool) ($data['has_header'] ?? true),
            columnCount: (int) ($data['column_count'] ?? 0),
            recordCount: (int) ($data['record_count'] ?? 0),
            emptyLineCount: (int) ($data['empty_line_count'] ?? 0),
            previewRecords: array_map(
                fn (array $row) => new CsvRecord((int) $row['line'], array_values(array_map('strval', $row['values']))),
                $data['preview'] ?? [],
            ),
            irregularRecordCount: (int) ($data['irregular_record_count'] ?? 0),
            irregularLines: array_map('intval', $data['irregular_lines'] ?? []),
        );
    }
}

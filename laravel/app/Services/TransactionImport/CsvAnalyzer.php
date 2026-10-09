<?php

namespace App\Services\TransactionImport;

use App\Exceptions\TransactionsImportException;

class CsvAnalyzer
{
    public const int PREVIEW_ROWS = 5;

    private const int MAX_LISTED_LINES = 20;

    private const int DELIMITER_SAMPLE_LINES = 20;

    public function __construct(private readonly CsvReader $reader) {}

    /**
     * @throws TransactionsImportException
     */
    public function analyze(string $path, CsvFormat $format, int $previewRows = self::PREVIEW_ROWS): CsvAnalysis
    {
        $header = $this->reader->header($path, $format);
        $headerWidth = count($header);
        $columnCount = $headerWidth;
        $recordCount = 0;
        $emptyLineCount = 0;
        $preview = [];
        $irregularCount = 0;
        $irregularLines = [];

        foreach ($this->reader->records($path, $format) as $record) {
            if ($record->isEmpty()) {
                $emptyLineCount++;

                continue;
            }

            $recordCount++;
            $width = count($record->values);
            $columnCount = max($columnCount, $width);

            if ($headerWidth > 0 && $width !== $headerWidth) {
                $irregularCount++;
                if (count($irregularLines) < self::MAX_LISTED_LINES) {
                    $irregularLines[] = $record->line;
                }
            }

            if (count($preview) < $previewRows) {
                $preview[] = $record;
            }
        }

        if ($format->hasHeader && $header === []) {
            throw new TransactionsImportException(
                'Es wurde keine Header-Zeile gefunden. Bitte Trennzeichen und Anzahl der Zeilen vor dem Header prüfen.'
            );
        }

        // ohne Header-Zeile werden generische Spaltennamen vergeben; fehlende Header-Namen werden aufgefüllt
        for ($index = count($header); $index < $columnCount; $index++) {
            $header[] = CsvReader::columnLabel($index);
        }

        return new CsvAnalysis(
            header: $header,
            hasHeader: $format->hasHeader,
            columnCount: $columnCount,
            recordCount: $recordCount,
            emptyLineCount: $emptyLineCount,
            previewRecords: $preview,
            irregularRecordCount: $irregularCount,
            irregularLines: $irregularLines,
        );
    }

    /**
     * Erkennt das Feldtrennzeichen anhand der ersten Zeilen nach der Präambel: gewählt wird das Zeichen,
     * das die meisten Spalten bei möglichst gleichbleibender Spaltenanzahl erzeugt.
     *
     * @throws TransactionsImportException
     */
    public function detectDelimiter(string $path, CsvFormat $format): string
    {
        $bestDelimiter = $format->delimiter;
        $bestScore = 0;

        foreach (CsvFormat::DELIMITERS as $delimiter) {
            $candidate = $format->withDelimiter($delimiter);
            $widths = [];

            foreach ($this->reader->records($path, new CsvFormat(
                delimiter: $delimiter,
                enclosure: $candidate->enclosure,
                escape: $candidate->escape,
                encoding: $candidate->encoding,
                hasHeader: false,
                headerOffset: $candidate->headerOffset,
            )) as $record) {
                if ($record->isEmpty()) {
                    continue;
                }

                $widths[] = count($record->values);

                if (count($widths) >= self::DELIMITER_SAMPLE_LINES) {
                    break;
                }
            }

            if ($widths === []) {
                continue;
            }

            $counts = array_count_values($widths);
            arsort($counts);
            $mostCommonWidth = (int) array_key_first($counts);
            // Spaltenanzahl * Anteil der Zeilen mit dieser Spaltenanzahl
            $score = $mostCommonWidth > 1 ? $mostCommonWidth * ($counts[$mostCommonWidth] / count($widths)) : 0;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }
}

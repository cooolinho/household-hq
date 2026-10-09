<?php

namespace Tests\Unit\TransactionImport;

use App\Exceptions\TransactionsImportException;
use App\Services\TransactionImport\CsvAnalyzer;
use App\Services\TransactionImport\CsvEncoding;
use App\Services\TransactionImport\CsvFormat;
use App\Services\TransactionImport\CsvReader;
use Tests\TestCase;

class CsvAnalyzerTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    public function test_it_reads_comma_separated_files_with_quotes_and_escaped_quotes(): void
    {
        $path = $this->csv("Datum,Text,Betrag\n2026-01-02,\"Miete, Januar\",-800.00\n2026-01-03,\"Er sagte \"\"Hallo\"\"\",5.00\n");

        $analysis = $this->analyzer()->analyze($path, new CsvFormat(delimiter: ','));

        self::assertSame(['Datum', 'Text', 'Betrag'], $analysis->header);
        self::assertSame(2, $analysis->recordCount);
        self::assertSame('Miete, Januar', $analysis->previewRecords[0]->value(1));
        self::assertSame('Er sagte "Hallo"', $analysis->previewRecords[1]->value(1));
    }

    public function test_it_reads_semicolon_files_with_preamble_bom_and_multiline_values(): void
    {
        $path = $this->csv("\xEF\xBB\xBFKonto;DE00\n\nBuchung;Verwendungszweck;Betrag\n01.02.2026;\"Zeile 1\nZeile 2\";-1,00\n02.02.2026;Kaffee;-2,50\n");

        $analysis = $this->analyzer()->analyze($path, new CsvFormat(delimiter: ';', headerOffset: 2));

        self::assertSame(['Buchung', 'Verwendungszweck', 'Betrag'], $analysis->header);
        self::assertSame(2, $analysis->recordCount);
        self::assertSame(4, $analysis->previewRecords[0]->line);
        self::assertSame("Zeile 1\nZeile 2", $analysis->previewRecords[0]->value(1));
        self::assertSame(6, $analysis->previewRecords[1]->line);
    }

    public function test_it_reports_rows_with_a_different_column_count_and_empty_lines(): void
    {
        $path = $this->csv("A;B;C\n1;2;3\n\n1;2\n1;2;3;4\n");

        $analysis = $this->analyzer()->analyze($path, new CsvFormat);

        self::assertSame(3, $analysis->recordCount);
        self::assertSame(1, $analysis->emptyLineCount);
        self::assertSame(4, $analysis->columnCount);
        self::assertSame(['A', 'B', 'C', 'Spalte 4'], $analysis->header);
        self::assertSame(2, $analysis->irregularRecordCount);
        self::assertSame([4, 5], $analysis->irregularLines);
    }

    public function test_files_without_header_get_generic_column_names(): void
    {
        $path = $this->csv("01.01.2026;-5,00\n02.01.2026;7,00\n");

        $analysis = $this->analyzer()->analyze($path, new CsvFormat(hasHeader: false));

        self::assertSame(['Spalte 1', 'Spalte 2'], $analysis->header);
        self::assertSame(2, $analysis->recordCount);
    }

    public function test_it_detects_the_delimiter(): void
    {
        $semicolon = $this->csv("Datum;Text;Betrag\n01.01.2026;Miete, Januar;-800,00\n");
        $comma = $this->csv("Date,Text,Amount\n2026-01-01,Rent,-800.00\n");

        self::assertSame(';', $this->analyzer()->detectDelimiter($semicolon, new CsvFormat(delimiter: ',')));
        self::assertSame(',', $this->analyzer()->detectDelimiter($comma, new CsvFormat));
    }

    public function test_it_converts_windows_1252_to_utf8(): void
    {
        $path = $this->csv("Empf\xE4nger;Betrag\nM\xFCller;1,00\n");

        $analysis = $this->analyzer()->analyze($path, new CsvFormat(encoding: CsvEncoding::Auto));

        self::assertSame('Empfänger', $analysis->header[0]);
        self::assertSame('Müller', $analysis->previewRecords[0]->value(0));
    }

    public function test_it_fails_when_the_header_row_is_missing(): void
    {
        $path = $this->csv("A;B\n");

        $this->expectException(TransactionsImportException::class);
        $this->analyzer()->analyze($path, new CsvFormat(headerOffset: 5));
    }

    private function analyzer(): CsvAnalyzer
    {
        return new CsvAnalyzer(new CsvReader);
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_analyzer_');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }

    protected function tearDown(): void
    {
        array_map('unlink', $this->files);

        parent::tearDown();
    }
}

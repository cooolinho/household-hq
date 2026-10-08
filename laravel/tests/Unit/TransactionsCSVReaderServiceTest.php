<?php

namespace Tests\Unit;

use App\Services\TransactionsCSVReaderService;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class TransactionsCSVReaderServiceTest extends TestCase
{
    public function test_normalize_csv_value_converts_windows_1252_bytes_to_utf8(): void
    {
        $service = new TransactionsCSVReaderService();
        $method = $this->getMethod('normalizeCsvValue');

        $expected = 'Sparkasse ßenknecht';
        $legacyEncoded = 'Sparkasse ' . chr(223) . 'enknecht';

        self::assertSame($expected, $method->invoke($service, $legacyEncoded));
    }

    public function test_normalize_csv_value_keeps_utf8_unchanged(): void
    {
        $service = new TransactionsCSVReaderService();
        $method = $this->getMethod('normalizeCsvValue');

        $value = 'Apotheke Müller';

        self::assertSame($value, $method->invoke($service, $value));
    }

    public function test_parse_decimal_value_keeps_cent_values_with_comma_separator(): void
    {
        $service = new TransactionsCSVReaderService();
        $method = $this->getMethod('parseDecimalValue');

        self::assertSame(21.84, $method->invoke($service, '21,84'));
        self::assertSame(-7.99, $method->invoke($service, '-7,99'));
    }

    public function test_parse_decimal_value_supports_thousands_and_dot_decimals(): void
    {
        $service = new TransactionsCSVReaderService();
        $method = $this->getMethod('parseDecimalValue');

        $this->setAmountFormat($service, 'de_de');

        self::assertSame(1234.56, $method->invoke($service, '1.234,56'));
        self::assertSame(1890.70, $method->invoke($service, '1.890,70'));
        self::assertSame(1913.50, $method->invoke($service, '1.913,50'));
        self::assertNull($method->invoke($service, '1,234.56'));
        self::assertNull($method->invoke($service, 'ABC'));
    }

    public function test_parse_decimal_value_supports_english_preset(): void
    {
        $service = new TransactionsCSVReaderService();
        $method = $this->getMethod('parseDecimalValue');

        $this->setAmountFormat($service, 'en_us');

        self::assertSame(1234.56, $method->invoke($service, '1,234.56'));
        self::assertSame(1890.70, $method->invoke($service, '1,890.70'));
        self::assertNull($method->invoke($service, '1.234,56'));
        self::assertNull($method->invoke($service, 'ABC'));
    }

    public function test_load_header_reads_header_row_from_offset(): void
    {
        $service = new TransactionsCSVReaderService();
        $this->setOffsetHeader($service, 2);

        $csvContent = "Preamble line 1\nPreamble line 2\nDate;Payer;Amount\n01.01.2024;Test;1,00\n";
        $filePath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($filePath, $csvContent);

        try {
            $handle = fopen($filePath, 'r');
            $method = $this->getMethod('loadHeader');
            $method->invoke($service, $handle);
            fclose($handle);

            $headerProperty = new ReflectionProperty(TransactionsCSVReaderService::class, 'header');
            self::assertSame(['Date', 'Payer', 'Amount'], $headerProperty->getValue($service));
        } finally {
            unlink($filePath);
        }
    }

    public function test_load_header_reads_first_row_when_no_offset_is_set(): void
    {
        $service = new TransactionsCSVReaderService();

        $csvContent = "Date;Payer;Amount\n01.01.2024;Test;1,00\n";
        $filePath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($filePath, $csvContent);

        try {
            $handle = fopen($filePath, 'r');
            $method = $this->getMethod('loadHeader');
            $method->invoke($service, $handle);
            fclose($handle);

            $headerProperty = new ReflectionProperty(TransactionsCSVReaderService::class, 'header');
            self::assertSame(['Date', 'Payer', 'Amount'], $headerProperty->getValue($service));
        } finally {
            unlink($filePath);
        }
    }

    public function test_load_header_returns_empty_header_when_offset_exceeds_file_length(): void
    {
        $service = new TransactionsCSVReaderService();
        $this->setOffsetHeader($service, 10);

        $csvContent = "Line 1\nLine 2\n";
        $filePath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($filePath, $csvContent);

        try {
            $handle = fopen($filePath, 'r');
            $method = $this->getMethod('loadHeader');
            $method->invoke($service, $handle);
            fclose($handle);

            $headerProperty = new ReflectionProperty(TransactionsCSVReaderService::class, 'header');
            self::assertSame([], $headerProperty->getValue($service));
        } finally {
            unlink($filePath);
        }
    }

    public function test_load_imports_ing_csv_rows_after_header_at_line_fourteen(): void
    {
        $profile = new CSVImportProfile();
        $profile->forceFill([
            CSVImportProfile::delimiter => ';',
            CSVImportProfile::enclosure => '"',
            CSVImportProfile::escape => '\\',
            CSVImportProfile::amount_format => 'de_de',
            CSVImportProfile::offset_header => 13,
            CSVImportProfile::mapping => [
                Transaction::date => 0,
                Transaction::value_date => 1,
                Transaction::payer => 2,
                Transaction::description => 3,
                Transaction::purpose => 4,
                Transaction::balance => 6,
                Transaction::balance_currency => 7,
                Transaction::amount => 8,
                Transaction::amount_currency => 9,
            ],
        ]);

        $csvContent = "Umsatzanzeige;Datei erstellt am: 08.10.2026 15:57\n"
            . "\n"
            . "IBAN;DE43 5001 0517 5444 7582 19\n"
            . "Kontoname;Girokonto\n"
            . "Bank;ING\n"
            . "Kunde;Colin Deepe\n"
            . "Zeitraum;08.09.2026 - 08.10.2026\n"
            . "Saldo;1.072,61;EUR\n"
            . "\n"
            . "Sortierung;Datum absteigend\n"
            . "\n"
            . "In der CSV-Datei finden Sie alle bereits gebuchten Umsätze.\n"
            . "\n"
            . "Buchung;Wertstellungsdatum;Auftraggeber/Empfänger;Buchungstext;Verwendungszweck;Referenz;Saldo;Währung;Betrag;Währung\n"
            . "07.10.2026;07.10.2026;VISA ANTHROPIC;Lastschrift;NR XXXX 2012 ANTHROPIC.C US KAUFUMSATZ;;1.072,61;EUR;-23,80;EUR\n"
            . "07.10.2026;07.10.2026;Turn- und Sportverein von 1908 Grosenkneten e.V.;Lastschrift;Beitragseinzug TSV von 1908 Grosenkneten e.V.;20260725-C000000006;1.096,41;EUR;-30,00;EUR\n";
        $filePath = tempnam(sys_get_temp_dir(), 'ing_csv_test_');
        file_put_contents($filePath, $csvContent);

        try {
            $csvFile = (new TransactionsCSVReaderService())->load($filePath, $profile);

            self::assertSame('Buchung', $csvFile->getHeader()[0]);
            self::assertCount(2, $csvFile->getRecords());
            self::assertSame(-23.8, $csvFile->getRecords()[0][Transaction::amount]);
            self::assertSame(1072.61, $csvFile->getRecords()[0][Transaction::balance]);
            self::assertSame(-30.0, $csvFile->getRecords()[1][Transaction::amount]);
            self::assertSame(1096.41, $csvFile->getRecords()[1][Transaction::balance]);
        } finally {
            unlink($filePath);
        }
    }

    public function test_load_imports_vr_bank_csv_with_one_currency_column(): void
    {
        $profile = new CSVImportProfile();
        $profile->forceFill([
            CSVImportProfile::delimiter => ';',
            CSVImportProfile::enclosure => '"',
            CSVImportProfile::escape => '\\',
            CSVImportProfile::amount_format => 'de_de',
            CSVImportProfile::offset_header => 0,
            CSVImportProfile::mapping => [
                Transaction::date => 4,
                Transaction::value_date => 5,
                Transaction::payer => 6,
                Transaction::description => 9,
                Transaction::purpose => 10,
                Transaction::balance => 13,
                Transaction::balance_currency => 12,
                Transaction::amount => 11,
                Transaction::amount_currency => 12,
            ],
        ]);

        $csvContent = "Bezeichnung Auftragskonto;IBAN Auftragskonto;BIC Auftragskonto;Bankname Auftragskonto;Buchungstag;Valutadatum;Name Zahlungsbeteiligter;IBAN Zahlungsbeteiligter;BIC (SWIFT-Code) Zahlungsbeteiligter;Buchungstext;Verwendungszweck;Betrag;Waehrung;Saldo nach Buchung;Bemerkung;Gekennzeichneter Umsatz;Glaeubiger ID;Mandatsreferenz\n"
            . "RegionalKonto online;DE62280662142403271900;GENODEF1WDH;VOLKSBANK OLDENBURG-LAND DELMENHORST;02.10.2026;02.10.2026;HOL AB GETRAENKEMARKT;DE86300500000001052141;WELADEDDXXX;Kartenzahlung girocard;Hol ab Getraenkemarkt GmbH/Grossenkneten/DE 01.10.2026;-29,87;EUR;115,99;;;DE95ZZZ00000082261;OFFLINE\n"
            . "RegionalKonto online;DE62280662142403271900;GENODEF1WDH;VOLKSBANK OLDENBURG-LAND DELMENHORST;02.10.2026;02.10.2026;www.euronicsxxl-boeseleger.de;DE41280501000028426328;SLZODE22XXX;Kartenzahlung girocard;www.euronicsxxl-boeseleger.de/Westring 8/Wildeshausen;-43,99;EUR;145,86;;;DE42ZZZ00002865175;332270\n";
        $filePath = tempnam(sys_get_temp_dir(), 'vr_bank_csv_test_');
        file_put_contents($filePath, $csvContent);

        try {
            $csvFile = (new TransactionsCSVReaderService())->load($filePath, $profile);
            $records = $csvFile->getRecords();

            self::assertCount(18, $csvFile->getHeader());
            self::assertSame('Buchungstag', $csvFile->getHeader()[4]);
            self::assertCount(2, $records);
            self::assertSame('2026-10-02', $records[0][Transaction::date]);
            self::assertSame('HOL AB GETRAENKEMARKT', $records[0][Transaction::payer]);
            self::assertSame('Kartenzahlung girocard', $records[0][Transaction::description]);
            self::assertSame(-29.87, $records[0][Transaction::amount]);
            self::assertSame(115.99, $records[0][Transaction::balance]);
            self::assertSame('EUR', $records[0][Transaction::amount_currency]);
            self::assertSame('EUR', $records[0][Transaction::balance_currency]);
            self::assertSame(-43.99, $records[1][Transaction::amount]);
            self::assertSame(145.86, $records[1][Transaction::balance]);
        } finally {
            unlink($filePath);
        }
    }

    private function getMethod(string $methodName): ReflectionMethod
    {
        $method = new ReflectionMethod(TransactionsCSVReaderService::class, $methodName);

        return $method;
    }

    private function setAmountFormat(TransactionsCSVReaderService $service, string $amountFormat): void
    {
        $property = new ReflectionProperty(TransactionsCSVReaderService::class, 'amountFormat');
        $property->setValue($service, $amountFormat);
    }

    private function setOffsetHeader(TransactionsCSVReaderService $service, int $offsetHeader): void
    {
        $property = new ReflectionProperty(TransactionsCSVReaderService::class, 'offsetHeader');
        $property->setValue($service, $offsetHeader);
    }
}


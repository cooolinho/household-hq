<?php

namespace Tests\Unit\TransactionImport;

use App\Services\TransactionImport\AmountFormat;
use App\Services\TransactionImport\CsvFormat;
use App\Services\TransactionImport\ImportProblem;
use App\Services\TransactionImport\InvalidValueException;
use App\Services\TransactionImport\TransactionImportField;
use App\Services\TransactionImport\ValueTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ValueTransformerTest extends TestCase
{
    private ValueTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new ValueTransformer;
    }

    /** @return array<string, array{string, string}> */
    public static function dates(): array
    {
        return [
            'deutsch' => ['31.12.2026', '2026-12-31'],
            'deutsch ohne führende Nullen' => ['1.2.2026', '2026-02-01'],
            'deutsch zweistelliges Jahr' => ['31.12.26', '2026-12-31'],
            'ISO' => ['2026-12-31', '2026-12-31'],
            'ISO mit Uhrzeit' => ['2026-12-31 10:15:00', '2026-12-31'],
            'Schrägstrich' => ['31/12/2026', '2026-12-31'],
            'Bindestrich' => ['31-12-2026', '2026-12-31'],
        ];
    }

    #[DataProvider('dates')]
    public function test_it_parses_supported_date_formats(string $raw, string $expected): void
    {
        self::assertSame($expected, $this->transformer->toDate($raw));
    }

    public function test_it_uses_the_date_format_of_the_profile(): void
    {
        self::assertSame('2026-12-31', $this->transformer->toDate('12/31/2026', 'm/d/Y'));

        $this->expectException(InvalidValueException::class);
        $this->transformer->toDate('31.12.2026', 'm/d/Y');
    }

    /** @return array<string, array{string}> */
    public static function invalidDates(): array
    {
        return [
            'Text' => ['gestern'],
            'nicht existierender Tag' => ['31.02.2026'],
            'Monat 13' => ['01.13.2026'],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_it_rejects_invalid_dates(string $raw): void
    {
        try {
            $this->transformer->toDate($raw);
            self::fail('Expected InvalidValueException');
        } catch (InvalidValueException $exception) {
            self::assertSame(ImportProblem::InvalidDate, $exception->problem);
        }
    }

    /** @return array<string, array{string, AmountFormat, float}> */
    public static function amounts(): array
    {
        return [
            'deutsch Dezimalkomma' => ['12,34', AmountFormat::German, 12.34],
            'deutsch mit Tausenderpunkt' => ['1.234,56', AmountFormat::German, 1234.56],
            'deutsch negativ' => ['-42,50', AmountFormat::German, -42.5],
            'deutsch nachgestelltes Minus' => ['42,50-', AmountFormat::German, -42.5],
            'deutsch mit Währung' => ['1.234,56 €', AmountFormat::German, 1234.56],
            'deutsch nur Tausenderpunkt' => ['1.234', AmountFormat::German, 1234.0],
            'deutsch Dezimalpunkt' => ['12.34', AmountFormat::German, 12.34],
            'englisch' => ['1,234.56', AmountFormat::English, 1234.56],
            'englisch Dezimalkomma' => ['12,34', AmountFormat::English, 12.34],
            'englisch negativ' => ['-7.99', AmountFormat::English, -7.99],
            'automatisch deutsch' => ['1.234,56', AmountFormat::Auto, 1234.56],
            'automatisch englisch' => ['1,234.56', AmountFormat::Auto, 1234.56],
            'automatisch Komma' => ['12,34', AmountFormat::Auto, 12.34],
            'automatisch Punkt' => ['12.34', AmountFormat::Auto, 12.34],
            'Pluszeichen' => ['+100', AmountFormat::German, 100.0],
        ];
    }

    #[DataProvider('amounts')]
    public function test_it_parses_amounts(string $raw, AmountFormat $format, float $expected): void
    {
        self::assertSame($expected, $this->transformer->toDecimal($raw, $format));
    }

    /** @return array<string, array{string, AmountFormat}> */
    public static function invalidAmounts(): array
    {
        return [
            'Text' => ['ABC', AmountFormat::German],
            'englisches Format im deutschen Profil' => ['1,234.56', AmountFormat::German],
            'deutsches Format im englischen Profil' => ['1.234,56', AmountFormat::English],
            'Formel' => ['=1+2', AmountFormat::German],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_it_rejects_invalid_amounts(string $raw, AmountFormat $format): void
    {
        try {
            $this->transformer->toDecimal($raw, $format);
            self::fail('Expected InvalidValueException');
        } catch (InvalidValueException $exception) {
            self::assertSame(ImportProblem::InvalidAmount, $exception->problem);
        }
    }

    public function test_empty_values_become_null(): void
    {
        $format = new CsvFormat;

        self::assertNull($this->transformer->transform(TransactionImportField::Amount, '', $format));
        self::assertNull($this->transformer->transform(TransactionImportField::Date, '   ', $format));
        self::assertNull($this->transformer->transform(TransactionImportField::Purpose, null, $format));
    }

    public function test_text_is_kept_verbatim_and_limited_for_varchar_columns(): void
    {
        $format = new CsvFormat;

        self::assertSame('=HYPERLINK("x")', $this->transformer->transform(TransactionImportField::Purpose, '=HYPERLINK("x")', $format));

        $this->expectException(InvalidValueException::class);
        $this->transformer->transform(TransactionImportField::AmountCurrency, str_repeat('E', 256), $format);
    }

    public function test_it_converts_booleans_and_enums(): void
    {
        self::assertTrue($this->transformer->toBoolean('Ja'));
        self::assertFalse($this->transformer->toBoolean('0'));
        self::assertSame('csv', $this->transformer->toEnum('CSV', TestImportSource::class));
        self::assertSame('manual', $this->transformer->toEnum('Manual', TestImportSource::class));

        $this->expectException(InvalidValueException::class);
        $this->transformer->toEnum('excel', TestImportSource::class);
    }
}

enum TestImportSource: string
{
    case Csv = 'csv';
    case Manual = 'manual';
}

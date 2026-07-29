<?php

namespace Tests\Unit;

use App\Services\TransactionsCSVReaderService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

        self::assertSame(1234.56, $method->invoke($service, '1.234,56'));
        self::assertSame(1234.56, $method->invoke($service, '1,234.56'));
        self::assertNull($method->invoke($service, 'ABC'));
    }

    private function getMethod(string $methodName): ReflectionMethod
    {
        $method = new ReflectionMethod(TransactionsCSVReaderService::class, $methodName);

        return $method;
    }
}


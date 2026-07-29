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
        $method = $this->getNormalizeMethod();

        $expected = 'Sparkasse ßenknecht';
        $legacyEncoded = 'Sparkasse ' . chr(223) . 'enknecht';

        self::assertSame($expected, $method->invoke($service, $legacyEncoded));
    }

    private function getNormalizeMethod(): ReflectionMethod
    {
        $method = new ReflectionMethod(TransactionsCSVReaderService::class, 'normalizeCsvValue');

        return $method;
    }

    public function test_normalize_csv_value_keeps_utf8_unchanged(): void
    {
        $service = new TransactionsCSVReaderService();
        $method = $this->getNormalizeMethod();

        $value = 'Apotheke Müller';

        self::assertSame($value, $method->invoke($service, $value));
    }
}


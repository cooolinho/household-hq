<?php

namespace Tests\Unit;

use App\Models\Financial\Transaction;
use App\Services\RecurringTransactionSuggestionService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RecurringTransactionSuggestionServiceTest extends TestCase
{
    public function test_build_name_hint_prefers_purpose(): void
    {
        $service = new RecurringTransactionSuggestionService();
        $method = new ReflectionMethod(RecurringTransactionSuggestionService::class, 'buildNameHint');

        $transaction = new Transaction([
            Transaction::payer => 'Stadtwerke GmbH',
            Transaction::purpose => 'Stromabschlag Juli',
        ]);

        self::assertSame('Stromabschlag Juli', $method->invoke($service, $transaction));
    }

    public function test_build_fingerprint_is_stable_for_same_input(): void
    {
        $service = new RecurringTransactionSuggestionService();
        $method = new ReflectionMethod(RecurringTransactionSuggestionService::class, 'buildFingerprint');

        $txA = new Transaction([
            Transaction::payer => 'Abo Dienst',
            Transaction::purpose => 'Premium',
            Transaction::amount => -12.99,
            Transaction::amount_currency => 'EUR',
        ]);

        $txB = new Transaction([
            Transaction::payer => 'Abo Dienst',
            Transaction::purpose => 'Premium',
            Transaction::amount => -12.99,
            Transaction::amount_currency => 'EUR',
        ]);

        self::assertSame($method->invoke($service, $txA), $method->invoke($service, $txB));
    }
}


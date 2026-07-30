<?php

namespace Tests\Unit;

use App\Models\Financial\Transaction;
use App\Services\FixedCostMatchingLearningService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FixedCostMatchingLearningServiceTest extends TestCase
{
    public function test_normalize_text_reduces_noise_and_limits_length(): void
    {
        $service = new FixedCostMatchingLearningService();
        $method = new ReflectionMethod(FixedCostMatchingLearningService::class, 'normalizeText');

        $normalized = $method->invoke($service, '  NETFLIX---ABO ### 2026  ');

        self::assertSame('netflix abo 2026', $normalized);
    }

    public function test_build_descriptors_creates_payer_purpose_and_combined_rule(): void
    {
        $service = new FixedCostMatchingLearningService();
        $method = new ReflectionMethod(FixedCostMatchingLearningService::class, 'buildDescriptors');

        $transaction = new Transaction([
            Transaction::payer => 'Spotify AB',
            Transaction::purpose => 'Spotify Family',
        ]);

        $descriptors = $method->invoke($service, $transaction);

        self::assertCount(3, $descriptors);
        self::assertArrayHasKey('fingerprint', $descriptors[0]);
    }
}


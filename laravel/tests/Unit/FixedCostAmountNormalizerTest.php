<?php

namespace Tests\Unit;

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use App\Services\FixedCost\FixedCostAmountNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FixedCostAmountNormalizerTest extends TestCase
{
    use RefreshDatabase;

    private function makeFixedCost(array $attributes): FixedCost
    {
        return new FixedCost(array_merge([
            FixedCost::name => 'Test',
            FixedCost::amount => -100,
        ], $attributes));
    }

    /**
     * @return array<string, array{0: string, 1: float}>
     */
    public static function standardIntervalProvider(): array
    {
        return [
            'weekly' => [FixedCostIntervalEnum::WEEKLY->name, 52.0],
            'two weeks' => [FixedCostIntervalEnum::TWO_WEEKS->name, 26.0],
            'monthly' => [FixedCostIntervalEnum::MONTHLY->name, 12.0],
            'two months' => [FixedCostIntervalEnum::TWO_MONTHS->name, 6.0],
            'quarterly' => [FixedCostIntervalEnum::QUARTERLY->name, 4.0],
            'half yearly' => [FixedCostIntervalEnum::HALF_YEARLY->name, 2.0],
            'yearly' => [FixedCostIntervalEnum::YEARLY->name, 1.0],
        ];
    }

    #[DataProvider('standardIntervalProvider')]
    public function test_it_returns_occurrences_per_year_for_every_standard_interval(string $interval, float $expected): void
    {
        $fixedCost = $this->makeFixedCost([FixedCost::interval => $interval]);

        self::assertSame($expected, app(FixedCostAmountNormalizer::class)->occurrencesPerYear($fixedCost));
    }

    public function test_it_derives_the_monthly_factor_from_a_custom_interval(): void
    {
        $normalizer = app(FixedCostAmountNormalizer::class);

        $everyFourMonths = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => 4,
            FixedCost::custom_interval_unit => FixedCostIntervalUnitEnum::MONTH->name,
        ]);

        self::assertEqualsWithDelta(0.25, $normalizer->monthlyFactor($everyFourMonths), 0.0000001);

        $everyTenDays = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => 10,
            FixedCost::custom_interval_unit => FixedCostIntervalUnitEnum::DAY->name,
        ]);

        self::assertEqualsWithDelta(365.25 / 10 / 12, $normalizer->monthlyFactor($everyTenDays), 0.0000001);
    }

    public function test_it_returns_zero_for_an_incomplete_custom_interval_instead_of_throwing(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => null,
            FixedCost::custom_interval_unit => null,
        ]);

        $normalizer = app(FixedCostAmountNormalizer::class);

        self::assertFalse($normalizer->isConfigurationValid($fixedCost));
        self::assertSame(0.0, $normalizer->monthlyFactor($fixedCost));
        self::assertSame(0.0, $normalizer->yearlyFactor($fixedCost));
        self::assertSame(0.0, $normalizer->monthlyAmount($fixedCost));
    }

    public function test_it_returns_zero_for_an_unknown_interval_string(): void
    {
        $fixedCost = $this->makeFixedCost([FixedCost::interval => 'NOT_A_REAL_INTERVAL']);

        self::assertSame(0.0, app(FixedCostAmountNormalizer::class)->monthlyFactor($fixedCost));
    }
}

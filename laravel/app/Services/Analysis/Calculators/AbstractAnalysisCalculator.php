<?php

namespace App\Services\Analysis\Calculators;

use App\Models\AnalysisCard;
use App\Services\Analysis\AnalysisCalculatorContract;
use App\Services\Analysis\AnalysisConfiguration;

/**
 * Gemeinsame Helfer für alle Analysis-Calculator (Geld-Formatierung, Farbpalette).
 */
abstract class AbstractAnalysisCalculator implements AnalysisCalculatorContract
{
    /**
     * CVD-taugliche Farbpalette (analog zu den bestehenden Statistics-Widgets).
     *
     * @var list<string>
     */
    protected const array PALETTE = [
        'rgba(59, 130, 246, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(239, 68, 68, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
        'rgba(14, 165, 233, 0.8)',
        'rgba(251, 146, 60, 0.8)',
        'rgba(20, 184, 166, 0.8)',
        'rgba(168, 85, 247, 0.8)',
    ];

    protected function config(AnalysisCard $card): AnalysisConfiguration
    {
        $raw = is_array($card->{AnalysisCard::configuration}) ? $card->{AnalysisCard::configuration} : [];

        return AnalysisConfiguration::fromArray($card->{AnalysisCard::module}, $raw);
    }

    protected function formatMoney(float $value, string $currency, bool $signed = false): string
    {
        $prefix = $signed && $value > 0 ? '+' : '';

        return $prefix.number_format($value, 2, ',', '.').' '.$currency;
    }

    protected function formatPercent(float $value): string
    {
        return number_format($value, 1, ',', '.').' %';
    }

    /**
     * @return list<string>
     */
    protected function colors(int $count): array
    {
        $colors = [];

        for ($i = 0; $i < $count; $i++) {
            $colors[] = self::PALETTE[$i % count(self::PALETTE)];
        }

        return $colors;
    }
}

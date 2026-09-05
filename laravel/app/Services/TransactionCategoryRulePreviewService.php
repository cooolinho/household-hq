<?php

namespace App\Services;

use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Carbon\CarbonImmutable;

class TransactionCategoryRulePreviewService
{
    private const string PREVIEW_DATE = '01.01.2026';

    private const string DEFAULT_CURRENCY = 'EUR';

    /**
     * @return array<string, mixed>
     */
    public function build(TransactionCategoryRule $rule): array
    {
        $criteria = $rule->relationLoaded(TransactionCategoryRule::has_many_criteria)
            ? $rule->criteria
            : $rule->criteria()->get();

        $transaction = [
            TransactionCategoryCriterion::FIELD_DATE => null,
            TransactionCategoryCriterion::FIELD_PAYER => null,
            TransactionCategoryCriterion::FIELD_DESCRIPTION => null,
            TransactionCategoryCriterion::FIELD_PURPOSE => null,
            TransactionCategoryCriterion::FIELD_AMOUNT => null,
            TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY => null,
        ];

        $criteriaPreview = [];

        foreach ($criteria as $criterion) {
            $sampleValue = $this->getSampleValue($criterion);
            $field = $criterion->field;
            $matchesPreview = $this->matchesPreview($criterion, $sampleValue);

            if (array_key_exists($field, $transaction) && $transaction[$field] === null) {
                $transaction[$field] = $sampleValue;
            }

            $criteriaPreview[] = [
                'field' => $field,
                'field_label' => $this->getFieldLabel($field),
                'operator' => $criterion->operator,
                'operator_label' => $this->getOperatorLabel($criterion->operator),
                'value' => $criterion->value,
                'value_secondary' => $criterion->value_secondary,
                'sample_value' => $sampleValue,
                'matches_preview' => $matchesPreview,
                'value_segments' => $this->buildValueSegments($criterion->value, $matchesPreview),
                'value_secondary_segments' => $this->buildValueSegments(
                    $criterion->value_secondary,
                    $matchesPreview,
                ),
                'case_sensitive' => (bool)$criterion->case_sensitive,
            ];
        }

        if ($transaction[TransactionCategoryCriterion::FIELD_DATE] === null) {
            $transaction[TransactionCategoryCriterion::FIELD_DATE] = self::PREVIEW_DATE;
        }

        if ($transaction[TransactionCategoryCriterion::FIELD_AMOUNT] !== null
            && $transaction[TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY] === null
        ) {
            $transaction[TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY] = self::DEFAULT_CURRENCY;
        }

        return [
            'rule_id' => (int)$rule->getKey(),
            'operator' => $rule->operator,
            'operator_label' => $rule->operator === TransactionCategoryRule::OPERATOR_AND
                ? 'Alle Kriterien müssen zutreffen'
                : 'Mindestens ein Kriterium muss zutreffen',
            'active' => (bool)$rule->active,
            'transaction' => $transaction,
            'transaction_segments' => $this->buildTransactionSegments($transaction, $criteriaPreview),
            'criteria' => $criteriaPreview,
        ];
    }

    private function getSampleValue(TransactionCategoryCriterion $criterion): string|float|null
    {
        return match ($criterion->field) {
            TransactionCategoryCriterion::FIELD_DATE => $this->getSampleDate($criterion->value),
            TransactionCategoryCriterion::FIELD_AMOUNT => $this->getSampleAmount($criterion),
            default => $this->getSampleText($criterion),
        };
    }

    private function getSampleDate(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return $value !== '' ? $value : self::PREVIEW_DATE;
        }

        if (!checkdate(
            (int)substr($value, 5, 2),
            (int)substr($value, 8, 2),
            (int)substr($value, 0, 4),
        )) {
            return $value;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof CarbonImmutable ? $date->format('d.m.Y') : $value;
    }

    private function getSampleAmount(TransactionCategoryCriterion $criterion): ?float
    {
        $value = $this->toFloat($criterion->value);

        if ($value === null) {
            return null;
        }

        return match ($criterion->operator) {
            TransactionCategoryCriterion::OP_GREATER_THAN => $value + 1,
            TransactionCategoryCriterion::OP_LESS_THAN => $value - 1,
            TransactionCategoryCriterion::OP_BETWEEN => $this->getBetweenValue($value, $criterion->value_secondary),
            default => $value,
        };
    }

    private function toFloat(?string $value): ?float
    {
        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return (float)$value;
    }

    private function getBetweenValue(float $minimum, ?string $maximum): float
    {
        $maximumValue = $this->toFloat($maximum);

        return $maximumValue === null ? $minimum : ($minimum + $maximumValue) / 2;
    }

    private function getSampleText(TransactionCategoryCriterion $criterion): string
    {
        $value = trim($criterion->value);

        if ($value === '') {
            return 'Beispielwert';
        }

        return match ($criterion->operator) {
            TransactionCategoryCriterion::OP_CONTAINS => 'Beispiel ' . $value,
            TransactionCategoryCriterion::OP_STARTS_WITH => $value . ' Beispiel',
            TransactionCategoryCriterion::OP_ENDS_WITH => 'Beispiel ' . $value,
            TransactionCategoryCriterion::OP_REGEX => 'Beispiel passend zu ' . $value,
            default => $value,
        };
    }

    private function matchesPreview(TransactionCategoryCriterion $criterion, string|float|null $sampleValue): bool
    {
        if ($sampleValue === null) {
            return false;
        }

        if ($criterion->field === TransactionCategoryCriterion::FIELD_AMOUNT) {
            $actual = $this->toFloat((string)$sampleValue);
            $expected = $this->toFloat($criterion->value);

            if ($actual === null || $expected === null) {
                return false;
            }

            return match ($criterion->operator) {
                TransactionCategoryCriterion::OP_EQUALS => $actual === $expected,
                TransactionCategoryCriterion::OP_GREATER_THAN => $actual > $expected,
                TransactionCategoryCriterion::OP_LESS_THAN => $actual < $expected,
                TransactionCategoryCriterion::OP_BETWEEN => $this->matchesBetween(
                    $actual,
                    $expected,
                    $criterion->value_secondary,
                ),
                default => false,
            };
        }

        $actual = $this->getComparableSampleValue($criterion, (string)$sampleValue);
        $expected = $criterion->value;

        return match ($criterion->operator) {
            TransactionCategoryCriterion::OP_EQUALS => $this->matchesEquals(
                $actual,
                $expected,
                (bool)$criterion->case_sensitive,
            ),
            TransactionCategoryCriterion::OP_CONTAINS => $this->matchesContains(
                $actual,
                $expected,
                (bool)$criterion->case_sensitive,
            ),
            TransactionCategoryCriterion::OP_STARTS_WITH => $this->matchesStartsWith(
                $actual,
                $expected,
                (bool)$criterion->case_sensitive,
            ),
            TransactionCategoryCriterion::OP_ENDS_WITH => $this->matchesEndsWith(
                $actual,
                $expected,
                (bool)$criterion->case_sensitive,
            ),
            TransactionCategoryCriterion::OP_REGEX => $this->matchesRegex($actual, $expected),
            default => false,
        };
    }

    private function matchesBetween(float $actual, float $minimum, ?string $maximum): bool
    {
        $maximumValue = $this->toFloat($maximum);

        return $maximumValue !== null && $actual >= $minimum && $actual <= $maximumValue;
    }

    private function getComparableSampleValue(
        TransactionCategoryCriterion $criterion,
        string                       $sampleValue,
    ): string
    {
        if ($criterion->field !== TransactionCategoryCriterion::FIELD_DATE) {
            return $sampleValue;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($criterion->value)) === 1
            ? trim($criterion->value)
            : $sampleValue;
    }

    private function matchesEquals(string $actual, string $expected, bool $caseSensitive): bool
    {
        return $caseSensitive
            ? $actual === $expected
            : mb_strtolower($actual) === mb_strtolower($expected);
    }

    private function matchesContains(string $actual, string $expected, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return str_contains(mb_strtolower($actual), mb_strtolower($expected));
        }

        return str_contains($actual, $expected);
    }

    private function matchesStartsWith(string $actual, string $expected, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return str_starts_with(mb_strtolower($actual), mb_strtolower($expected));
        }

        return str_starts_with($actual, $expected);
    }

    private function matchesEndsWith(string $actual, string $expected, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return str_ends_with(mb_strtolower($actual), mb_strtolower($expected));
        }

        return str_ends_with($actual, $expected);
    }

    private function matchesRegex(string $actual, string $pattern): bool
    {
        return @preg_match($pattern, $actual) === 1;
    }

    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            TransactionCategoryCriterion::FIELD_DATE => 'Buchungsdatum',
            TransactionCategoryCriterion::FIELD_PAYER => 'Auftraggeber',
            TransactionCategoryCriterion::FIELD_DESCRIPTION => 'Beschreibung',
            TransactionCategoryCriterion::FIELD_PURPOSE => 'Verwendungszweck',
            TransactionCategoryCriterion::FIELD_AMOUNT => 'Betrag',
            TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY => 'Währung',
            default => $field,
        };
    }

    private function getOperatorLabel(string $operator): string
    {
        return match ($operator) {
            TransactionCategoryCriterion::OP_EQUALS => 'Ist gleich',
            TransactionCategoryCriterion::OP_CONTAINS => 'Enthält',
            TransactionCategoryCriterion::OP_STARTS_WITH => 'Beginnt mit',
            TransactionCategoryCriterion::OP_ENDS_WITH => 'Endet mit',
            TransactionCategoryCriterion::OP_REGEX => 'Regex',
            TransactionCategoryCriterion::OP_GREATER_THAN => 'Größer als',
            TransactionCategoryCriterion::OP_LESS_THAN => 'Kleiner als',
            TransactionCategoryCriterion::OP_BETWEEN => 'Zwischen',
            default => $operator,
        };
    }

    /**
     * @return array<int, array{text: string, matched: bool}>
     */
    private function buildValueSegments(?string $value, bool $matched): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return $this->buildSegments(
            $value,
            $matched ? [['start' => 0, 'length' => strlen($value)]] : [],
        );
    }

    /**
     * @param array<int, array{start: int, length: int}> $ranges
     * @return array<int, array{text: string, matched: bool}>
     */
    private function buildSegments(string $text, array $ranges): array
    {
        if ($text === '') {
            return [];
        }

        $cursor = 0;
        $segments = [];
        $textLength = strlen($text);

        foreach ($this->mergeRanges($ranges) as $range) {
            $start = $range['start'];
            $end = min($textLength, $start + $range['length']);

            if ($start > $cursor) {
                $segments[] = [
                    'text' => substr($text, $cursor, $start - $cursor),
                    'matched' => false,
                ];
            }

            if ($end > $start) {
                $segments[] = [
                    'text' => substr($text, $start, $end - $start),
                    'matched' => true,
                ];
                $cursor = max($cursor, $end);
            }
        }

        if ($cursor < $textLength) {
            $segments[] = [
                'text' => substr($text, $cursor),
                'matched' => false,
            ];
        }

        return $segments;
    }

    /**
     * @param array<int, array{start: int, length: int}> $ranges
     * @return array<int, array{start: int, length: int}>
     */
    private function mergeRanges(array $ranges): array
    {
        $ranges = array_values(array_filter(
            $ranges,
            fn(array $range): bool => $range['length'] > 0,
        ));

        usort(
            $ranges,
            fn(array $left, array $right): int => $left['start'] <=> $right['start'],
        );

        $merged = [];

        foreach ($ranges as $range) {
            $lastKey = array_key_last($merged);

            if ($lastKey === null) {
                $merged[] = $range;

                continue;
            }

            $last = $merged[$lastKey];
            $lastEnd = $last['start'] + $last['length'];
            $rangeEnd = $range['start'] + $range['length'];

            if ($range['start'] <= $lastEnd) {
                $merged[$lastKey]['length'] = max($lastEnd, $rangeEnd) - $last['start'];

                continue;
            }

            $merged[] = $range;
        }

        return $merged;
    }

    /**
     * @param array<int, array<string, mixed>> $criteria
     * @return array<string, array<int, array{text: string, matched: bool}>>
     */
    private function buildTransactionSegments(array $transaction, array $criteria): array
    {
        $segments = [];
        $fields = [
            TransactionCategoryCriterion::FIELD_DATE,
            TransactionCategoryCriterion::FIELD_PAYER,
            TransactionCategoryCriterion::FIELD_DESCRIPTION,
            TransactionCategoryCriterion::FIELD_PURPOSE,
            TransactionCategoryCriterion::FIELD_AMOUNT,
            TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY,
        ];

        foreach ($fields as $field) {
            $displayValue = $this->getTransactionDisplayValue($field, $transaction[$field] ?? null);

            if ($displayValue === null) {
                $segments[$field] = [];

                continue;
            }

            $fieldCriteria = array_values(array_filter(
                $criteria,
                fn(array $criterion): bool => $criterion['field'] === $field,
            ));

            if ($field === TransactionCategoryCriterion::FIELD_AMOUNT
                || $field === TransactionCategoryCriterion::FIELD_DATE
            ) {
                $matched = collect($fieldCriteria)
                    ->contains(fn(array $criterion): bool => (bool)$criterion['matches_preview']);
                $ranges = $matched ? [['start' => 0, 'length' => strlen($displayValue)]] : [];
            } else {
                $ranges = [];

                foreach ($fieldCriteria as $criterion) {
                    if (!$criterion['matches_preview']) {
                        continue;
                    }

                    $ranges = [
                        ...$ranges,
                        ...$this->getMatchRanges(
                            $displayValue,
                            $criterion['operator'],
                            $criterion['value'],
                            (bool)$criterion['case_sensitive'],
                        ),
                    ];
                }
            }

            $segments[$field] = $this->buildSegments($displayValue, $ranges);
        }

        return $segments;
    }

    private function getTransactionDisplayValue(string $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($field === TransactionCategoryCriterion::FIELD_AMOUNT) {
            return number_format((float)$value, 2, ',', '.');
        }

        return (string)$value;
    }

    /**
     * @return array<int, array{start: int, length: int}>
     */
    private function getMatchRanges(
        string $text,
        string $operator,
        string $value,
        bool   $caseSensitive,
    ): array
    {
        if ($text === '' || $value === '') {
            return [];
        }

        $modifiers = $caseSensitive ? 'u' : 'iu';
        $pattern = match ($operator) {
            TransactionCategoryCriterion::OP_EQUALS => '/\A' . preg_quote($value, '/') . '\z/' . $modifiers,
            TransactionCategoryCriterion::OP_CONTAINS => '/' . preg_quote($value, '/') . '/' . $modifiers,
            TransactionCategoryCriterion::OP_STARTS_WITH => '/\A' . preg_quote($value, '/') . '/' . $modifiers,
            TransactionCategoryCriterion::OP_ENDS_WITH => '/' . preg_quote($value, '/') . '\z/' . $modifiers,
            TransactionCategoryCriterion::OP_REGEX => $value,
            default => null,
        };

        if ($pattern === null) {
            return [];
        }

        $matchCount = @preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        if ($matchCount === false || $matchCount === 0 || !isset($matches[0])) {
            return [];
        }

        $ranges = [];

        foreach ($matches[0] as $match) {
            if (!is_array($match) || !is_string($match[0]) || !is_int($match[1])) {
                continue;
            }

            $length = strlen($match[0]);

            if ($length > 0) {
                $ranges[] = [
                    'start' => $match[1],
                    'length' => $length,
                ];
            }
        }

        return $ranges;
    }
}

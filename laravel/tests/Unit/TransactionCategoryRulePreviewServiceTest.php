<?php

namespace Tests\Unit;

use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Services\TransactionCategoryRulePreviewService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class TransactionCategoryRulePreviewServiceTest extends TestCase
{
    public function test_builds_a_transaction_preview_from_text_criteria(): void
    {
        $rule = $this->makeRule([
            new TransactionCategoryCriterion([
                TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PURPOSE,
                TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
                TransactionCategoryCriterion::value => 'streaming',
                TransactionCategoryCriterion::case_sensitive => false,
            ]),
        ]);

        $preview = (new TransactionCategoryRulePreviewService)->build($rule);

        self::assertSame('Beispiel streaming', $preview['transaction'][TransactionCategoryCriterion::FIELD_PURPOSE]);
        self::assertSame('Verwendungszweck', $preview['criteria'][0]['field_label']);
        self::assertSame('Enthält', $preview['criteria'][0]['operator_label']);
        self::assertSame([
            ['text' => 'Beispiel ', 'matched' => false],
            ['text' => 'streaming', 'matched' => true],
        ], $preview['transaction_segments'][TransactionCategoryCriterion::FIELD_PURPOSE]);
        self::assertTrue($preview['criteria'][0]['matches_preview']);
        self::assertSame([
            ['text' => 'streaming', 'matched' => true],
        ], $preview['criteria'][0]['value_segments']);
    }

    /**
     * @param array<int, TransactionCategoryCriterion> $criteria
     */
    private function makeRule(array $criteria): TransactionCategoryRule
    {
        $rule = new TransactionCategoryRule([
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_AND,
            TransactionCategoryRule::active => true,
        ]);
        $rule->setAttribute(TransactionCategoryRule::id, 42);
        $rule->setRelation(TransactionCategoryRule::has_many_criteria, new Collection($criteria));

        return $rule;
    }

    public function test_builds_a_numeric_and_date_preview(): void
    {
        $rule = $this->makeRule([
            new TransactionCategoryCriterion([
                TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_AMOUNT,
                TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_BETWEEN,
                TransactionCategoryCriterion::value => '10',
                TransactionCategoryCriterion::value_secondary => '20',
            ]),
            new TransactionCategoryCriterion([
                TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_DATE,
                TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_EQUALS,
                TransactionCategoryCriterion::value => '2026-09-05',
            ]),
        ]);

        $preview = (new TransactionCategoryRulePreviewService)->build($rule);

        self::assertSame(15.0, $preview['transaction'][TransactionCategoryCriterion::FIELD_AMOUNT]);
        self::assertSame('EUR', $preview['transaction'][TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY]);
        self::assertSame('05.09.2026', $preview['transaction'][TransactionCategoryCriterion::FIELD_DATE]);
        self::assertSame([
            ['text' => '15,00', 'matched' => true],
        ], $preview['transaction_segments'][TransactionCategoryCriterion::FIELD_AMOUNT]);
    }

    public function test_highlights_only_the_regex_match_in_the_preview_text(): void
    {
        $rule = $this->makeRule([
            new TransactionCategoryCriterion([
                TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PAYER,
                TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_REGEX,
                TransactionCategoryCriterion::value => '/spotify/i',
            ]),
        ]);

        $preview = (new TransactionCategoryRulePreviewService)->build($rule);

        self::assertSame([
            ['text' => 'Beispiel passend zu /', 'matched' => false],
            ['text' => 'spotify', 'matched' => true],
            ['text' => '/i', 'matched' => false],
        ], $preview['transaction_segments'][TransactionCategoryCriterion::FIELD_PAYER]);
        self::assertTrue($preview['criteria'][0]['matches_preview']);
    }
}

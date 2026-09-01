<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Illuminate\Database\Seeder;

/**
 * TransactionCategorySeeder
 *
 * Erstellt Demo-Transaktionskategorien mit Regeln und Kriterien.
 *
 * Struktur (Hauptkategorie > Unterkategorien):
 *  - Abonnements > Musik Streaming
 *  - Abonnements > Video Streaming
 *  - Abonnements > Cloud Dienste
 *  - Finanzen > PayPal
 *  - Finanzen > Amazon
 *  - Einkommen > Gehalt
 *  - Einkommen > Sonstige Einnahmen
 *  - Energie & Wohnen > Strom
 *  - Energie & Wohnen > Miete
 *  - Energie & Wohnen > Internet
 */
class TransactionCategorySeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt Demo-Transaktionskategorien mit Regeln und Kriterien an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        $structure = [
            'Abonnements' => [
                'Musik Streaming' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'spotify'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'spotify'],
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'apple music'],
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'deezer'],
                        ],
                    ],
                ],
                'Video Streaming' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'netflix'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'netflix'],
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'disney'],
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'amazon prime'],
                        ],
                    ],
                ],
                'Cloud Dienste' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'google one'],
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'dropbox'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'icloud'],
                        ],
                    ],
                ],
            ],
            'Finanzen' => [
                'PayPal' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'paypal'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'paypal'],
                        ],
                    ],
                ],
                'Amazon' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'amazon'],
                        ],
                    ],
                ],
            ],
            'Einkommen' => [
                'Gehalt' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'gehalt'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'lohn'],
                            [TransactionCategoryCriterion::FIELD_DESCRIPTION, TransactionCategoryCriterion::OP_CONTAINS, 'lohnzahlung'],
                            [TransactionCategoryCriterion::FIELD_DESCRIPTION, TransactionCategoryCriterion::OP_CONTAINS, 'gehaltszahlung'],
                        ],
                    ],
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_AND,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_AMOUNT, TransactionCategoryCriterion::OP_GREATER_THAN, '1000'],
                            [TransactionCategoryCriterion::FIELD_DESCRIPTION, TransactionCategoryCriterion::OP_CONTAINS, 'überweisung'],
                        ],
                    ],
                ],
                'Sonstige Einnahmen' => [],
            ],
            'Energie & Wohnen' => [
                'Strom' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_AND,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'stadtwerke'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'strom'],
                        ],
                    ],
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'stromabschlag'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'strom abschlag'],
                        ],
                    ],
                ],
                'Miete' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'miete'],
                            [TransactionCategoryCriterion::FIELD_DESCRIPTION, TransactionCategoryCriterion::OP_CONTAINS, 'dauerauftrag'],
                        ],
                    ],
                ],
                'Internet' => [
                    [
                        'operator' => TransactionCategoryRule::OPERATOR_OR,
                        'criteria' => [
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'telekom'],
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'unitymedia'],
                            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'vodafone'],
                            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'internet'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($structure as $parentName => $children) {
            $parent = TransactionCategory::query()->firstOrCreate(
                [
                    TransactionCategory::user_id => null,
                    TransactionCategory::name => $parentName,
                    TransactionCategory::parent_id => null,
                ],
                [
                    TransactionCategory::active => true,
                ],
            );

            foreach ($children as $childName => $rules) {
                $child = TransactionCategory::query()->firstOrCreate(
                    [
                        TransactionCategory::user_id => null,
                        TransactionCategory::name => $childName,
                        TransactionCategory::parent_id => $parent->id,
                    ],
                    [
                        TransactionCategory::active => true,
                    ],
                );

                foreach ($rules as $ruleDef) {
                    $rule = TransactionCategoryRule::query()->firstOrCreate(
                        [
                            TransactionCategoryRule::transaction_category_id => $child->getKey(),
                            TransactionCategoryRule::user_id => null,
                            TransactionCategoryRule::operator => $ruleDef['operator'],
                        ],
                        [
                            TransactionCategoryRule::active => true,
                        ],
                    );

                    foreach ($ruleDef['criteria'] as [$field, $operator, $value]) {
                        TransactionCategoryCriterion::query()->firstOrCreate(
                            [
                                TransactionCategoryCriterion::transaction_category_rule_id => $rule->getKey(),
                                TransactionCategoryCriterion::field => $field,
                                TransactionCategoryCriterion::operator => $operator,
                                TransactionCategoryCriterion::value => $value,
                            ],
                            [
                                TransactionCategoryCriterion::case_sensitive => false,
                            ],
                        );
                    }
                }
            }
        }
    }
}

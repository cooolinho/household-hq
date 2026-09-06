<?php

namespace Database\Seeders\Financial\CategoryRules;

use App\Models\Financial\TransactionCategoryCriterion;

/**
 * Regeldefinition einer Unterkategorie.
 *
 * merchants  Marken-/Firmennamen  -> geprüft auf payer + purpose
 * keywords   generische Begriffe  -> geprüft auf payer + purpose + description
 *
 * Generische Begriffe wie "Gaststätte", "Apotheke" oder "Autowerkstatt" stehen in
 * Kontoauszügen meist im Zahlungsempfänger, deshalb wird payer mitgeprüft.
 *
 * Beide Gruppen werden zu getrennten Systemregeln expandiert, damit ein User im
 * Admin-Panel z.B. die breite Stichwortregel deaktivieren, die Händlerregel aber
 * behalten kann.
 *
 * Ist ein amountGuard gesetzt, wird jede Regel je Feld in eine AND-Regel
 * aufgeteilt (Feldkriterium + Betragskriterium). Da Regeln untereinander mit OR
 * verknüpft sind, ergibt das insgesamt:
 *   (payer OR purpose OR description) AND amountGuard
 */
final readonly class SubcategoryRules
{
    public const int GUARD_NONE = 0;

    /** Nur Zahlungseingänge (amount > 0). */
    public const int GUARD_INCOME = 1;

    /** Nur Zahlungsausgänge (amount < 0). */
    public const int GUARD_EXPENSE = 2;

    /**
     * @param list<string> $merchants
     * @param list<string> $keywords
     * @param list<RuleDefinition> $additionalRules Regeln, die sich nicht über Keyword-Listen ausdrücken lassen
     */
    public function __construct(
        public array $merchants = [],
        public array $keywords = [],
        public int   $amountGuard = self::GUARD_NONE,
        public array $additionalRules = [],
    )
    {
    }

    /**
     * Expandiert die Definition zu konkreten Regeln.
     *
     * @return list<RuleDefinition>
     */
    public function toRules(string $keyPrefix): array
    {
        return [
            ...$this->expand($keyPrefix . '.merchants', $this->merchants, [
                TransactionCategoryCriterion::FIELD_PAYER,
                TransactionCategoryCriterion::FIELD_PURPOSE,
            ]),
            ...$this->expand($keyPrefix . '.keywords', $this->keywords, [
                TransactionCategoryCriterion::FIELD_PAYER,
                TransactionCategoryCriterion::FIELD_PURPOSE,
                TransactionCategoryCriterion::FIELD_DESCRIPTION,
            ]),
            ...$this->additionalRules,
        ];
    }

    /**
     * @param list<string> $keywords
     * @param list<string> $fields
     * @return list<RuleDefinition>
     */
    private function expand(string $key, array $keywords, array $fields): array
    {
        if ($keywords === []) {
            return [];
        }

        $patterns = KeywordPattern::build($keywords);

        if ($this->amountGuard === self::GUARD_NONE) {
            $criteria = [];

            foreach ($fields as $field) {
                foreach ($patterns as $pattern) {
                    $criteria[] = CriterionDefinition::regex($field, $pattern);
                }
            }

            return [RuleDefinition::anyOf($key, ...$criteria)];
        }

        // Mit Guard: je Feld/Pattern eine eigene AND-Regel, damit der Betrag immer greift.
        $guard = $this->amountGuard === self::GUARD_INCOME
            ? CriterionDefinition::amountGreaterThan(0)
            : CriterionDefinition::amountLessThan(0);

        $rules = [];

        foreach ($fields as $field) {
            foreach ($patterns as $index => $pattern) {
                $suffix = count($patterns) > 1 ? '.' . ($index + 1) : '';
                $rules[] = RuleDefinition::allOf(
                    $key . '.' . $field . $suffix,
                    CriterionDefinition::regex($field, $pattern),
                    $guard,
                );
            }
        }

        return $rules;
    }
}

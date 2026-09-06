<?php

namespace App\Models\Enums;

use Illuminate\Database\Eloquent\Builder;

/**
 * Legt fest, welche Transaktionen der verknüpften Kategorien als Fortschritt zählen.
 *
 * EXPENSE: Ausgaben (negative Beträge) zählen positiv, z. B. Raten oder Überweisungen auf ein Sparkonto.
 * INCOME: Einnahmen (positive Beträge) zählen, z. B. Gutschriften auf einem Sparkonto.
 * NET: Netto-Summe wie beim Budget, darf auch negativ werden.
 */
enum GoalDirectionEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case EXPENSE;
    case INCOME;
    case NET;

    public static function default(): string
    {
        return self::EXPENSE->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::EXPENSE => 'Ausgaben',
            self::INCOME => 'Einnahmen',
            self::NET => 'Netto',
        };
    }

    public function helperText(): string
    {
        return match ($this) {
            self::EXPENSE => 'Zählt Abbuchungen, z. B. Raten oder Überweisungen auf ein Sparkonto.',
            self::INCOME => 'Zählt Gutschriften, z. B. Eingänge auf einem Sparkonto.',
            self::NET => 'Zählt die Netto-Summe aller Buchungen (wie bei Budgets); Erstattungen mindern den Fortschritt.',
        };
    }

    /**
     * Schränkt die Query auf die für diese Richtung relevanten Beträge ein.
     *
     * @param Builder<\App\Models\Financial\Transaction> $query
     * @return Builder<\App\Models\Financial\Transaction>
     */
    public function applyAmountFilter(Builder $query): Builder
    {
        return match ($this) {
            self::EXPENSE => $query->where(\App\Models\Financial\Transaction::amount, '<', 0),
            self::INCOME => $query->where(\App\Models\Financial\Transaction::amount, '>', 0),
            self::NET => $query,
        };
    }

    /**
     * Wandelt die SQL-Summe der gefilterten Beträge in einen Fortschrittsbeitrag um.
     */
    public function normalize(float $sum): float
    {
        return match ($this) {
            self::EXPENSE => max(0.0, -1 * $sum),
            self::INCOME => max(0.0, $sum),
            self::NET => -1 * $sum,
        };
    }
}

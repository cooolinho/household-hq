<?php

namespace Database\Seeders\Financial\CategoryRules;

use Illuminate\Support\Str;

/**
 * Basis für die Regeldefinitionen einer Hauptkategorie.
 *
 * Jede Hauptkategorie besitzt eine eigene Provider-Klasse, die ihre
 * Unterkategorien samt kuratierter Erkennungsregeln liefert. Der
 * TransactionCategorySeeder enthält dadurch keine Fachdaten mehr.
 */
abstract class CategoryRuleProvider
{
    /**
     * Erzeugt die konkreten Regeln einer Unterkategorie inklusive stabiler Keys.
     *
     * @return list<RuleDefinition>
     */
    public static function rulesFor(string $subcategoryName): array
    {
        $definition = static::subcategories()[$subcategoryName] ?? null;

        if (!$definition instanceof SubcategoryRules) {
            return [];
        }

        return $definition->toRules(static::keyPrefix($subcategoryName));
    }

    /**
     * Unterkategoriename => Regeldefinition.
     *
     * @return array<string, SubcategoryRules>
     */
    abstract public static function subcategories(): array;

    public static function keyPrefix(string $subcategoryName): string
    {
        return self::slug(static::parentName()) . '.' . self::slug($subcategoryName);
    }

    private static function slug(string $value): string
    {
        return Str::slug($value, '-', 'de');
    }

    /**
     * Name der Hauptkategorie, z.B. 'Finanzen & Versicherungen'.
     */
    abstract public static function parentName(): string;

    /**
     * Alle Unterkategorienamen aller Hauptkategorien.
     *
     * @return list<string>
     */
    public static function allSubcategoryNames(): array
    {
        $names = [];

        foreach (self::all() as $provider) {
            $names = [...$names, ...array_keys($provider::subcategories())];
        }

        return $names;
    }

    /**
     * Registry aller Hauptkategorien – die Reihenfolge bestimmt die Seed-Reihenfolge.
     *
     * @return list<class-string<CategoryRuleProvider>>
     */
    public static function all(): array
    {
        return [
            FinanceInsuranceRules::class,
            LeisureEntertainmentRules::class,
            LivingExpensesRules::class,
            MobilityRules::class,
            GovernmentRules::class,
        ];
    }
}

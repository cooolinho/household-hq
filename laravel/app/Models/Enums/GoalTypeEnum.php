<?php

namespace App\Models\Enums;

use Filament\Support\Icons\Heroicon;

/**
 * Art des Ziels. Bestimmt, wie Startwert und Zielbetrag zueinander stehen und
 * mit welchen Begriffen der aktuelle Stand benannt wird.
 *
 * SAVINGS: start_amount = bereits vorhanden, target_amount = Zielbetrag (target > start).
 * DEBT_PAYOFF: start_amount = anfängliche Restschuld, target_amount = Restziel, i. d. R. 0 (target < start).
 */
enum GoalTypeEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case SAVINGS;
    case DEBT_PAYOFF;

    public static function default(): string
    {
        return self::SAVINGS->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::SAVINGS => 'Sparziel',
            self::DEBT_PAYOFF => 'Abzahlung',
        };
    }

    /**
     * Vollständiger Blade-Icon-Name, z. B. "heroicon-o-banknotes".
     */
    public function iconName(): string
    {
        return 'heroicon-' . $this->icon()->value;
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::SAVINGS => Heroicon::OutlinedBanknotes,
            self::DEBT_PAYOFF => Heroicon::OutlinedScale,
        };
    }

    /**
     * Beschriftung des Startwert-Feldes im Formular.
     */
    public function startLabel(): string
    {
        return match ($this) {
            self::SAVINGS => 'Bereits vorhanden',
            self::DEBT_PAYOFF => 'Anfängliche Restschuld',
        };
    }

    /**
     * Beschriftung des Zielbetrag-Feldes im Formular.
     */
    public function targetLabel(): string
    {
        return match ($this) {
            self::SAVINGS => 'Zielbetrag',
            self::DEBT_PAYOFF => 'Restschuld am Ende',
        };
    }

    /**
     * Beschriftung des aktuellen Standes auf Karte und Detail-Seite.
     */
    public function currentLabel(): string
    {
        return match ($this) {
            self::SAVINGS => 'Bereits gespart',
            self::DEBT_PAYOFF => 'Restschuld',
        };
    }

    /**
     * Beschriftung des noch offenen Betrages.
     */
    public function remainingLabel(): string
    {
        return match ($this) {
            self::SAVINGS => 'Noch zu sparen',
            self::DEBT_PAYOFF => 'Noch offen',
        };
    }

    /**
     * Beschriftung einer einzelnen Einzahlung.
     */
    public function contributionLabel(): string
    {
        return match ($this) {
            self::SAVINGS => 'Einzahlung',
            self::DEBT_PAYOFF => 'Tilgung',
        };
    }

    /**
     * Aktueller Stand: beim Sparziel wächst er vom Startwert aus, bei der Abzahlung schrumpft er.
     */
    public function currentAmount(float $startAmount, float $contributed): float
    {
        return match ($this) {
            self::SAVINGS => $startAmount + $contributed,
            self::DEBT_PAYOFF => $startAmount - $contributed,
        };
    }
}

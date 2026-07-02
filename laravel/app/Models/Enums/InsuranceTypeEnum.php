<?php

namespace App\Models\Enums;

enum InsuranceTypeEnum
{
    use UseEnumOptionsTrait;

    // Gruppe - Altersvorsorge
    case ZULAGENRENTE;
    case BETRIEBLICHE_ALTERSVORSORGE;
    case PERSOENLICHE_BASISVORSORGE;
    case FLEXIBLE_PRIVATVORSORGE;

    // Gruppe - Persönliche Absicherung
    case PRIVATE_KV_GESETZLICHE_KV;
    case KRANKENZUSATZ;
    case BERUFSUNFAEHIGKEIT;
    case PFLEGE;
    case UNFALL;
    case RISIKOLEBENSVERSICHERUNG;

    // Gruppe - Vermögensabsicherung
    case HAFTPFLICHT;
    case HAUSRAT;
    case RECHTSSCHUTZ;
    case KFZ;
    case SACHBUENDEL;

    // Gruppe - Vermögensaufbau
    case NOTGROSCHEN;
    case VERMOEGENSWIRKSAME_LEISTUNGEN;
    case SPARPLAENE;
    case EINMALANLAGEN;
    case SONSTIGE_VERMOEGEN;

    public static function options(): array
    {
        $options = [
            'Altersvorsorge' => [
                self::ZULAGENRENTE,
                self::BETRIEBLICHE_ALTERSVORSORGE,
                self::PERSOENLICHE_BASISVORSORGE,
                self::FLEXIBLE_PRIVATVORSORGE,
            ],
            'Persönliche Absicherung' => [
                self::PRIVATE_KV_GESETZLICHE_KV,
                self::KRANKENZUSATZ,
                self::BERUFSUNFAEHIGKEIT,
                self::PFLEGE,
                self::UNFALL,
                self::RISIKOLEBENSVERSICHERUNG,
            ],
            'Vermögensabsicherung' => [
                self::HAFTPFLICHT,
                self::HAUSRAT,
                self::RECHTSSCHUTZ,
                self::KFZ,
                self::SACHBUENDEL,
            ],
            'Vermögensaufbau' => [
                self::NOTGROSCHEN,
                self::VERMOEGENSWIRKSAME_LEISTUNGEN,
                self::SPARPLAENE,
                self::EINMALANLAGEN,
                self::SONSTIGE_VERMOEGEN,
            ],
        ];

        return array_map(function ($group) {
            $groupCases = array_map(fn(InsuranceTypeEnum $type) => [$type->name, $type->label()], $group);
            return array_combine(
                array_column($groupCases, 0),
                array_column($groupCases, 1)
            );
        }, $options);
    }

    public function label(): string
    {
        return match ($this) {

            // Gruppe - Altersvorsorge
            self::ZULAGENRENTE => 'Zulagenrente',
            self::BETRIEBLICHE_ALTERSVORSORGE => 'Betriebliche Altersvorsorge',
            self::PERSOENLICHE_BASISVORSORGE => 'Persönliche Basisvorsorge',
            self::FLEXIBLE_PRIVATVORSORGE => 'Flexible Privatvorsorge',

            // Gruppe - Persönliche Absicherung
            self::PRIVATE_KV_GESETZLICHE_KV => 'Private KV / Gesetzliche KV',
            self::KRANKENZUSATZ => 'Kranken-Zusatzversicherung',
            self::BERUFSUNFAEHIGKEIT => 'Berufsunfähigkeit',
            self::PFLEGE => 'Pflegeversicherung',
            self::UNFALL => 'Unfallversicherung',
            self::RISIKOLEBENSVERSICHERUNG => 'Risikolebensversicherung',

            // Gruppe - Vermögensabsicherung
            self::HAFTPFLICHT => 'Haftpflichtversicherung',
            self::HAUSRAT => 'Hausratversicherung',
            self::RECHTSSCHUTZ => 'Rechtsschutzversicherung',
            self::KFZ => 'Kfz-Versicherung',
            self::SACHBUENDEL => 'Sachbündelversicherung',

            // Gruppe - Vermögensaufbau
            self::NOTGROSCHEN => 'Notgroschen / Notfallfonds',
            self::VERMOEGENSWIRKSAME_LEISTUNGEN => 'Vermögenswirksame Leistungen (VL)',
            self::SPARPLAENE => 'Sparpläne (z.B. ETF, Fonds, Aktien)',
            self::EINMALANLAGEN => 'Einmalanlagen (z.B. Festgeld, Anleihen)',
            self::SONSTIGE_VERMOEGEN => 'Sonstige Vermögensaufbau-Produkte',
        };
    }
}

<?php

namespace App\Models\Enums;

enum FixedCostCategoryEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    const string GROUP_INSURANCES = 'Versicherungen';

    // Energy
    case ENERGY_ELECTRICITY;
    case ENERGY_GAS;
    case ENERGY_OIL;
    case ENERGY_WATER;
    case ENERGY_OTHER;

    // Einkommen
    case INCOME_LOHN_GEHALT;
    case INCOME_RENTE;
    case INCOME_KINDERGELD;
    case INCOME_ARBEITSLOSENGELD;
    case INCOME_OTHER;

    // Kommunikation
    case COMMUNICATION_INTERNET;
    case COMMUNICATION_MOBILE_PHONE;
    case COMMUNICATION_PHONE;
    case COMMUNICATION_CLOUD_STORAGE;
    case COMMUNICATION_OTHER;

    // Versicherungen
    case INSURANCE_CAR;
    case INSURANCE_BIKE;
    case INSURANCE_HEALTH;
    case INSURANCE_ACCIDENT;
    case INSURANCE_ERWERBSUNFAEHIGKEIT;
    case INSURANCE_HAUSRAT;
    case INSURANCE_GEBAEUDE;
    case INSURANCE_RECHTSSCHUTZ;
    case INSURANCE_LEBEN;
    case INSURANCE_KREDITE;
    case INSURANCE_HAFTPFLICHT;
    case INSURANCE_RENTE;
    case INSURANCE_OTHER;

    // Kredite
    case CREDIT_CAR;
    case CREDIT_UMSCHULDUNG;
    case CREDIT_ENTERTAINMENT;
    case CREDIT_MOEBEL_RENOVIERUNG;
    case CREDIT_IMMOBILIE;
    case CREDIT_STUDENT;
    case CREDIT_BAUSPARVERTRAG;
    case CREDIT_FREIE_VERFUEGUNG;

    // Unterhaltung
    case ENTERTAINMENT_MUSIC_STREAMING;
    case ENTERTAINMENT_RUNDFUNKBEITRAG;
    case ENTERTAINMENT_PAY_TV;
    case ENTERTAINMENT_VIDEO_STREAMING;
    case ENTERTAINMENT_OTHER;

    // Leasing
    case LEASING_CAR;
    case LEASING_OTHER;

    // Nicht kategorisiert
    case SPAREN;
    case SPENDEN;
    case HYPOTHEK_MIETE;
    case KINDERBETREUUNG;
    case HEALTHY;
    case BANKGEBUEHREN;
    case OTHER;

    public static function options(): array
    {
        return array_map(function ($group) {
            $groupCases = array_map(fn(FixedCostCategoryEnum $type) => [$type->name, $type->label()], $group);
            return array_combine(
                array_column($groupCases, 0),
                array_column($groupCases, 1)
            );
        }, self::groups());
    }

    public function label(): string
    {
        return match ($this) {
            self::ENERGY_ELECTRICITY => 'Strom',
            self::ENERGY_GAS => 'Gas',
            self::ENERGY_OIL => 'Öl',
            self::ENERGY_WATER => 'Wasser',
            self::ENERGY_OTHER => 'Sonstige Energie',

            self::INCOME_LOHN_GEHALT => 'Lohn / Gehalt',
            self::INCOME_RENTE => 'Rente',
            self::INCOME_KINDERGELD => 'Kindergeld',
            self::INCOME_ARBEITSLOSENGELD => 'Arbeitslosengeld',
            self::INCOME_OTHER => 'Sonstige Einnahmen',

            self::COMMUNICATION_INTERNET => 'Internet',
            self::COMMUNICATION_MOBILE_PHONE => 'Mobiltelefon',
            self::COMMUNICATION_PHONE => 'Telefon',
            self::COMMUNICATION_CLOUD_STORAGE => 'Cloud-Speicher',
            self::COMMUNICATION_OTHER => 'Sonstige Kommunikation',

            self::INSURANCE_CAR => 'Auto',
            self::INSURANCE_BIKE => 'Motorrad',
            self::INSURANCE_HEALTH => 'Gesundheit',
            self::INSURANCE_ACCIDENT => 'Unfall',
            self::INSURANCE_ERWERBSUNFAEHIGKEIT => 'Erwerbsunfähigkeit',
            self::INSURANCE_HAUSRAT => 'Hausrat',
            self::INSURANCE_GEBAEUDE => 'Gebäude',
            self::INSURANCE_RECHTSSCHUTZ => 'Rechtsschutz',
            self::INSURANCE_LEBEN => 'Leben',
            self::INSURANCE_KREDITE => 'Kredite',
            self::INSURANCE_HAFTPFLICHT => 'Haftpflicht',
            self::INSURANCE_RENTE => 'Rente',
            self::INSURANCE_OTHER => 'Sonstige Versicherungen',

            self::CREDIT_CAR => 'Autokredit',
            self::CREDIT_UMSCHULDUNG => 'Umschuldung',
            self::CREDIT_ENTERTAINMENT => 'Unterhaltungskredit',
            self::CREDIT_MOEBEL_RENOVIERUNG => 'Möbel / Renovierung',
            self::CREDIT_IMMOBILIE => 'Immobilienkredit',
            self::CREDIT_STUDENT => 'Studentenkredit',
            self::CREDIT_BAUSPARVERTRAG => 'Bausparvertrag',
            self::CREDIT_FREIE_VERFUEGUNG => 'Freie Verfügung',

            self::ENTERTAINMENT_MUSIC_STREAMING => 'Musik-Streaming',
            self::ENTERTAINMENT_RUNDFUNKBEITRAG => 'Rundfunkbeitrag',
            self::ENTERTAINMENT_PAY_TV => 'Pay-TV',
            self::ENTERTAINMENT_VIDEO_STREAMING => 'Video-Streaming',
            self::ENTERTAINMENT_OTHER => 'Sonstige Unterhaltung',

            self::LEASING_CAR => 'Autoleasing',
            self::LEASING_OTHER => 'Sonstiges Leasing',

            self::SPAREN => 'Sparen',
            self::SPENDEN => 'Spenden',
            self::HYPOTHEK_MIETE => 'Hypothek / Miete',
            self::KINDERBETREUUNG => 'Kinderbetreuung',
            self::HEALTHY => 'Gesundheit',
            self::BANKGEBUEHREN => 'Bankgebühren',
            self::OTHER => 'Sonstige',
        };
    }

    public static function groups(): array
    {
        return [
            'Energie' => [
                self::ENERGY_ELECTRICITY,
                self::ENERGY_GAS,
                self::ENERGY_OIL,
                self::ENERGY_WATER,
                self::ENERGY_OTHER,
            ],
            'Einkommen' => [
                self::INCOME_LOHN_GEHALT,
                self::INCOME_RENTE,
                self::INCOME_KINDERGELD,
                self::INCOME_ARBEITSLOSENGELD,
                self::INCOME_OTHER,
            ],
            'Kommunikation' => [
                self::COMMUNICATION_INTERNET,
                self::COMMUNICATION_MOBILE_PHONE,
                self::COMMUNICATION_PHONE,
                self::COMMUNICATION_CLOUD_STORAGE,
                self::COMMUNICATION_OTHER,
            ],
            self::GROUP_INSURANCES => [
                self::INSURANCE_CAR,
                self::INSURANCE_BIKE,
                self::INSURANCE_HEALTH,
                self::INSURANCE_ACCIDENT,
                self::INSURANCE_ERWERBSUNFAEHIGKEIT,
                self::INSURANCE_HAUSRAT,
                self::INSURANCE_GEBAEUDE,
                self::INSURANCE_RECHTSSCHUTZ,
                self::INSURANCE_LEBEN,
                self::INSURANCE_KREDITE,
                self::INSURANCE_HAFTPFLICHT,
                self::INSURANCE_RENTE,
                self::INSURANCE_OTHER,
            ],
            'Kredite' => [
                self::CREDIT_CAR,
                self::CREDIT_UMSCHULDUNG,
                self::CREDIT_ENTERTAINMENT,
                self::CREDIT_MOEBEL_RENOVIERUNG,
                self::CREDIT_IMMOBILIE,
                self::CREDIT_STUDENT,
                self::CREDIT_BAUSPARVERTRAG,
                self::CREDIT_FREIE_VERFUEGUNG,
            ],
            'Unterhaltung' => [
                self::ENTERTAINMENT_MUSIC_STREAMING,
                self::ENTERTAINMENT_RUNDFUNKBEITRAG,
                self::ENTERTAINMENT_PAY_TV,
                self::ENTERTAINMENT_VIDEO_STREAMING,
                self::ENTERTAINMENT_OTHER,
            ],
            'Leasing' => [
                self::LEASING_CAR,
                self::LEASING_OTHER,
            ],
            'Nicht kategorisiert' => [
                self::SPAREN,
                self::SPENDEN,
                self::HYPOTHEK_MIETE,
                self::KINDERBETREUUNG,
                self::HEALTHY,
                self::BANKGEBUEHREN,
                self::OTHER,
            ],
        ];
    }

    public static function default(): string
    {
        return self::OTHER->name;
    }

    public static function getCategoriesByGroup(string $name): array
    {
        return self::groups()[$name] ?? [];
    }
}

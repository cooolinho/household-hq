<?php

namespace App\Services\TransactionImport;

use BackedEnum;
use DateTimeImmutable;
use UnitEnum;

/**
 * Wandelt einen CSV-Rohwert in den speicherbaren Wert einer Transaction-Spalte um.
 * Der Zieltyp ergibt sich aus TransactionImportField::type() (= Casts des Transaction-Models).
 */
class ValueTransformer
{
    /**
     * Unterstützte Datumsformate, falls das Profil kein festes Format vorgibt.
     * Die Muster verhindern Fehlinterpretationen wie "31.12.26" als Jahr 26.
     *
     * @var array<string, string> Format => Muster
     */
    private const array DATE_FORMATS = [
        'd.m.Y' => '/^\d{1,2}\.\d{1,2}\.\d{4}$/',
        'd.m.y' => '/^\d{1,2}\.\d{1,2}\.\d{2}$/',
        'Y-m-d' => '/^\d{4}-\d{1,2}-\d{1,2}$/',
        'd/m/Y' => '/^\d{1,2}\/\d{1,2}\/\d{4}$/',
        'd-m-Y' => '/^\d{1,2}-\d{1,2}-\d{4}$/',
        'Y/m/d' => '/^\d{4}\/\d{1,2}\/\d{1,2}$/',
        'Ymd' => '/^\d{8}$/',
    ];

    private const array BOOLEAN_TRUE = ['1', 'true', 'ja', 'j', 'yes', 'y', 'x', 'wahr'];

    private const array BOOLEAN_FALSE = ['0', 'false', 'nein', 'n', 'no', 'falsch'];

    /** decimal(15, 2) */
    private const float MAX_AMOUNT = 9999999999999.99;

    /**
     * @return string|float|bool|int|null null bei leerem Wert
     *
     * @throws InvalidValueException
     */
    public function transform(TransactionImportField $field, ?string $raw, CsvFormat $format): string|float|bool|int|null
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        return match ($field->type()) {
            ValueType::Date => $this->toDate($raw, $format->dateFormat),
            ValueType::Decimal => $this->toDecimal($raw, $format->amountFormat),
            ValueType::Boolean => $this->toBoolean($raw),
            ValueType::Enum => $this->toEnum($raw, (string) $field->cast()),
            ValueType::Text => $this->toText($raw, $field->maxLength()),
        };
    }

    /**
     * @return string Datum im Format Y-m-d
     *
     * @throws InvalidValueException
     */
    public function toDate(string $raw, ?string $dateFormat = null): string
    {
        $value = trim($raw);

        if ($dateFormat !== null) {
            $date = $this->parseDate($dateFormat, $value);
        } else {
            // Uhrzeitanteile ("2026-12-31 00:00:00", "2026-12-31T10:00:00+01:00") werden ignoriert
            $datePart = preg_split('/[ T](?=\d{1,2}:\d{2})/', $value, 2)[0];
            $date = null;

            foreach (self::DATE_FORMATS as $format => $pattern) {
                if (preg_match($pattern, $datePart) === 1) {
                    $date = $this->parseDate($format, $datePart);
                    break;
                }
            }
        }

        if ($date === null) {
            $expected = $dateFormat !== null ? " (erwartet: {$dateFormat})" : '';

            throw new InvalidValueException(ImportProblem::InvalidDate, "Ungültiges Datum \"{$value}\"{$expected}.");
        }

        return $date->format('Y-m-d');
    }

    /**
     * @throws InvalidValueException
     */
    public function toDecimal(string $raw, AmountFormat $amountFormat = AmountFormat::German): float
    {
        $original = trim($raw);
        // Leerzeichen (auch geschützte), Apostrophe und Währungsangaben entfernen
        $value = preg_replace('/[\s\x{00A0}\x{202F}\']+|€|EUR/iu', '', $original) ?? '';

        $negative = false;
        if (str_ends_with($value, '-')) {
            // nachgestelltes Minus, z. B. "42,50-"
            $negative = true;
            $value = substr($value, 0, -1);
        }
        if (str_starts_with($value, '-') || str_starts_with($value, '+')) {
            $negative = $negative || $value[0] === '-';
            $value = substr($value, 1);
        }

        $normalized = $this->normalizeDecimal($value, $amountFormat);

        if ($normalized === null || abs((float) $normalized) > self::MAX_AMOUNT) {
            throw new InvalidValueException(ImportProblem::InvalidAmount, "Ungültiger Betrag \"{$original}\".");
        }

        $amount = round((float) $normalized, 2);

        return $negative && $amount !== 0.0 ? -$amount : $amount;
    }

    /**
     * @throws InvalidValueException
     */
    public function toBoolean(string $raw): bool
    {
        $value = mb_strtolower(trim($raw));

        if (in_array($value, self::BOOLEAN_TRUE, true)) {
            return true;
        }

        if (in_array($value, self::BOOLEAN_FALSE, true)) {
            return false;
        }

        throw new InvalidValueException(ImportProblem::InvalidBoolean, "Ungültiger Ja/Nein-Wert \"{$raw}\".");
    }

    /**
     * @param  class-string<UnitEnum>  $enumClass
     * @return string|int gespeicherter Wert des Enums (value bzw. Name)
     *
     * @throws InvalidValueException
     */
    public function toEnum(string $raw, string $enumClass): string|int
    {
        $value = trim($raw);

        foreach ($enumClass::cases() as $case) {
            $candidates = [$case->name];
            if ($case instanceof BackedEnum) {
                $candidates[] = (string) $case->value;
            }

            foreach ($candidates as $candidate) {
                if (mb_strtolower($candidate) === mb_strtolower($value)) {
                    return $case instanceof BackedEnum ? $case->value : $case->name;
                }
            }
        }

        throw new InvalidValueException(ImportProblem::InvalidEnum, "Unbekannter Wert \"{$value}\".");
    }

    /**
     * Texte werden unverändert übernommen, nie interpretiert oder ausgeführt. Sie werden bewusst nicht
     * getrimmt, damit der Duplikat-Hash (Transaction::createHash()) zu bereits importierten Daten passt.
     *
     * @throws InvalidValueException
     */
    public function toText(string $raw, ?int $maxLength = null): string
    {
        $value = $raw;

        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            throw new InvalidValueException(
                ImportProblem::TooLong,
                "Text ist länger als {$maxLength} Zeichen.",
            );
        }

        return $value;
    }

    private function parseDate(string $format, string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        $year = (int) $date->format('Y');

        return $year >= 1900 && $year <= 2200 ? $date : null;
    }

    /**
     * @return string|null Zahl mit "." als Dezimaltrenner, null wenn das Format nicht passt
     */
    private function normalizeDecimal(string $value, AmountFormat $amountFormat): ?string
    {
        if ($value === '' || preg_match('/^[\d.,]+$/', $value) !== 1) {
            return null;
        }

        [$decimal, $thousands] = match ($amountFormat) {
            AmountFormat::German => [',', '.'],
            AmountFormat::English => ['.', ','],
            AmountFormat::Auto => $this->detectSeparators($value),
        };

        // "12.34" im deutschen bzw. "12,34" im englischen Format: Tausendertrennzeichen stehen immer
        // vor genau drei Ziffern, ein einzelnes Trennzeichen mit 1-2 Nachkommastellen ist der Dezimaltrenner
        if (! str_contains($value, $decimal) && preg_match('/^\d+'.preg_quote($thousands, '/').'\d{1,2}$/', $value) === 1) {
            [$decimal, $thousands] = [$thousands, $decimal];
        }

        $pattern = '/^(?:\d{1,3}(?:'.preg_quote($thousands, '/').'\d{3})+|\d+)(?:'.preg_quote($decimal, '/').'\d+)?$/';

        if (preg_match($pattern, $value) !== 1) {
            return null;
        }

        return str_replace([$thousands, $decimal], ['', '.'], $value);
    }

    /**
     * Automatische Erkennung: Das zuletzt vorkommende Trennzeichen ist der Dezimaltrenner;
     * ein einzelnes Komma gilt (deutsche Bankexporte) als Dezimaltrenner.
     *
     * @return array{0: string, 1: string} [Dezimaltrenner, Tausendertrennzeichen]
     */
    private function detectSeparators(string $value): array
    {
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            return $lastComma > $lastDot ? [',', '.'] : ['.', ','];
        }

        if ($lastComma !== false) {
            return substr_count($value, ',') > 1 ? ['.', ','] : [',', '.'];
        }

        if ($lastDot !== false && substr_count($value, '.') > 1) {
            return [',', '.'];
        }

        return ['.', ','];
    }
}

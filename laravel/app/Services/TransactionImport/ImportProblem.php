<?php

namespace App\Services\TransactionImport;

/**
 * Art eines Problems in einem CSV-Datensatz; dient zur Gruppierung der Fehler in Prüfung und Ergebnis.
 */
enum ImportProblem: string
{
    case MissingRequired = 'missing_required';
    case MissingColumn = 'missing_column';
    case InvalidDate = 'invalid_date';
    case InvalidAmount = 'invalid_amount';
    case InvalidBoolean = 'invalid_boolean';
    case InvalidEnum = 'invalid_enum';
    case TooLong = 'too_long';

    /** Satz für die Zusammenfassung, z. B. "3 Datensätze enthalten ungültige Beträge." */
    public function summary(int $count): string
    {
        $records = $count === 1 ? '1 Datensatz' : $count.' Datensätze';
        $verb = $count === 1 ? 'enthält' : 'enthalten';

        return match ($this) {
            self::MissingRequired => "{$records} ohne Pflichtwert (Buchungsdatum/Betrag).",
            self::MissingColumn => "{$records} mit zu wenigen Spalten.",
            self::InvalidDate => "{$records} {$verb} ungültige Datumswerte.",
            self::InvalidAmount => "{$records} {$verb} ungültige Beträge.",
            self::InvalidBoolean => "{$records} {$verb} ungültige Ja/Nein-Werte.",
            self::InvalidEnum => "{$records} {$verb} unbekannte Auswahlwerte.",
            self::TooLong => "{$records} {$verb} zu lange Texte.",
        };
    }
}

<?php

namespace App\Services\TransactionImport;

use App\Models\Financial\Transaction;

/**
 * Explizite Import-Konfiguration: alle Transaction-Spalten, auf die eine CSV-Spalte gemappt werden darf.
 *
 * Systemspalten (user_id, bank_account_id, hash, fixed_cost_id, Timestamps) sind bewusst nicht enthalten
 * und werden ausschließlich vom Importer gesetzt. Der Datentyp wird aus den Model-Casts abgeleitet.
 */
enum TransactionImportField: string
{
    case Date = Transaction::date;
    case ValueDate = Transaction::value_date;
    case Payer = Transaction::payer;
    case Description = Transaction::description;
    case Purpose = Transaction::purpose;
    case Amount = Transaction::amount;
    case AmountCurrency = Transaction::amount_currency;
    case Balance = Transaction::balance;
    case BalanceCurrency = Transaction::balance_currency;

    public function label(): string
    {
        return match ($this) {
            self::Date => 'Buchungsdatum',
            self::ValueDate => 'Wertstellung',
            self::Payer => 'Auftraggeber/Empfänger',
            self::Description => 'Buchungstext',
            self::Purpose => 'Verwendungszweck',
            self::Amount => 'Betrag',
            self::AmountCurrency => 'Währung (Betrag)',
            self::Balance => 'Saldo',
            self::BalanceCurrency => 'Währung (Saldo)',
        };
    }

    /** Pflichtfelder entsprechen den NOT-NULL-Spalten der Tabelle financial_transactions. */
    public function isRequired(): bool
    {
        return in_array($this, [self::Date, self::Amount], true);
    }

    public function cast(): ?string
    {
        return (new Transaction)->getCasts()[$this->value] ?? null;
    }

    public function type(): ValueType
    {
        return ValueType::fromCast($this->cast());
    }

    /** Maximale Länge für Textspalten (VARCHAR), null = unbegrenzt (TEXT-Spalten). */
    public function maxLength(): ?int
    {
        return match ($this) {
            self::AmountCurrency, self::BalanceCurrency => 255,
            default => null,
        };
    }

    /**
     * Typische Spaltennamen deutscher/englischer Bankexporte, für den automatischen Mapping-Vorschlag.
     *
     * @return list<string>
     */
    public function aliases(): array
    {
        return match ($this) {
            self::Date => ['buchungsdatum', 'buchungstag', 'buchung', 'datum', 'date', 'booking date'],
            self::ValueDate => ['wertstellung', 'wertstellungsdatum', 'valutadatum', 'valuta', 'value date'],
            self::Payer => ['auftraggeber/empfänger', 'name zahlungsbeteiligter', 'zahlungsempfänger', 'empfänger', 'auftraggeber', 'beguenstigter/zahlungspflichtiger', 'payee', 'payer', 'name'],
            self::Description => ['buchungstext', 'umsatzart', 'beschreibung', 'description', 'text'],
            self::Purpose => ['verwendungszweck', 'zweck', 'purpose', 'reference'],
            self::Amount => ['betrag', 'betrag (€)', 'betrag (eur)', 'umsatz', 'amount'],
            self::AmountCurrency => ['währung', 'waehrung', 'currency'],
            self::Balance => ['saldo', 'saldo nach buchung', 'kontostand', 'balance'],
            self::BalanceCurrency => [],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $field) => $field->value, self::cases());
    }
}

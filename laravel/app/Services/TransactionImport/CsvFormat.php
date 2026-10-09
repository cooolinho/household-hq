<?php

namespace App\Services\TransactionImport;

use App\Models\Financial\CSVImportProfile;

/**
 * Format-/Parsing-Konfiguration einer CSV-Datei. Wird aus einem Import-Profil oder den Wizard-Eingaben erzeugt.
 */
final readonly class CsvFormat
{
    /** @var list<string> */
    public const array DELIMITERS = [';', ',', "\t", '|'];

    public function __construct(
        public string $delimiter = ';',
        public string $enclosure = '"',
        public string $escape = '\\',
        public CsvEncoding $encoding = CsvEncoding::Auto,
        public bool $hasHeader = true,
        public int $headerOffset = 0,
        public AmountFormat $amountFormat = AmountFormat::German,
        public ?string $dateFormat = null,
    ) {}

    /** @return array<string, string> */
    public static function delimiterOptions(): array
    {
        return [
            ';' => 'Semikolon ( ; )',
            ',' => 'Komma ( , )',
            "\t" => 'Tabulator',
            '|' => 'Senkrechter Strich ( | )',
        ];
    }

    public static function fromProfile(CSVImportProfile $profile): self
    {
        return self::fromArray([
            CSVImportProfile::delimiter => $profile->delimiter,
            CSVImportProfile::enclosure => $profile->enclosure,
            CSVImportProfile::escape => $profile->escape,
            CSVImportProfile::encoding => $profile->encoding,
            CSVImportProfile::has_header => $profile->has_header,
            CSVImportProfile::offset_header => $profile->offset_header,
            CSVImportProfile::amount_format => $profile->amount_format,
            CSVImportProfile::date_format => $profile->date_format,
        ]);
    }

    /**
     * Erzeugt das Format aus (unvalidierten) Formular-/Profilwerten; ungültige Angaben fallen auf Defaults zurück.
     *
     * @param  array<string, mixed>  $data  Schlüssel wie die CSVImportProfile-Spalten
     */
    public static function fromArray(array $data): self
    {
        $delimiter = (string) ($data[CSVImportProfile::delimiter] ?? '');
        $enclosure = (string) ($data[CSVImportProfile::enclosure] ?? '');
        $escape = (string) ($data[CSVImportProfile::escape] ?? '\\');
        $dateFormat = trim((string) ($data[CSVImportProfile::date_format] ?? ''));

        return new self(
            delimiter: in_array($delimiter, self::DELIMITERS, true) ? $delimiter : ';',
            enclosure: mb_strlen($enclosure) === 1 ? $enclosure : '"',
            // fgetcsv akzeptiert ein leeres Escape-Zeichen (= Escaping deaktiviert)
            escape: mb_strlen($escape) <= 1 ? $escape : '\\',
            encoding: CsvEncoding::tryFrom((string) ($data[CSVImportProfile::encoding] ?? '')) ?? CsvEncoding::Auto,
            hasHeader: (bool) ($data[CSVImportProfile::has_header] ?? true),
            headerOffset: max(0, min(255, (int) ($data[CSVImportProfile::offset_header] ?? 0))),
            amountFormat: AmountFormat::tryFrom((string) ($data[CSVImportProfile::amount_format] ?? '')) ?? AmountFormat::German,
            dateFormat: $dateFormat === '' ? null : $dateFormat,
        );
    }

    /** @return array<string, mixed> Schlüssel wie die CSVImportProfile-Spalten */
    public function toArray(): array
    {
        return [
            CSVImportProfile::delimiter => $this->delimiter,
            CSVImportProfile::enclosure => $this->enclosure,
            CSVImportProfile::escape => $this->escape,
            CSVImportProfile::encoding => $this->encoding->value,
            CSVImportProfile::has_header => $this->hasHeader,
            CSVImportProfile::offset_header => $this->headerOffset,
            CSVImportProfile::amount_format => $this->amountFormat->value,
            CSVImportProfile::date_format => $this->dateFormat,
        ];
    }

    public function withDelimiter(string $delimiter): self
    {
        return new self(
            $delimiter,
            $this->enclosure,
            $this->escape,
            $this->encoding,
            $this->hasHeader,
            $this->headerOffset,
            $this->amountFormat,
            $this->dateFormat,
        );
    }
}

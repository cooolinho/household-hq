<?php

namespace App\Services\TransactionImport;

use App\Models\Financial\BankAccount;

/**
 * Alles, was der Importer für eine Datei braucht: Format, Spalten-Mapping, Zielkonto und Importeur.
 */
final readonly class ImportConfiguration
{
    /**
     * @param  array<string, int|null>  $columns  Transaction-Feld => 0-basierter Spaltenindex (null = nicht importieren)
     * @param  bool  $skipInvalidRows  fehlerhafte Datensätze überspringen statt den Import zu blockieren
     */
    public function __construct(
        public CsvFormat $format,
        public array $columns,
        public BankAccount $bankAccount,
        public int $userId,
        public bool $skipInvalidRows = false,
    ) {}

    public function columnFor(TransactionImportField $field): ?int
    {
        return $this->columns[$field->value] ?? null;
    }

    /**
     * Fehler, die das gesamte Mapping betreffen (nicht einzelne Datensätze).
     *
     * @return list<string>
     */
    public function mappingErrors(): array
    {
        $errors = [];

        foreach (TransactionImportField::cases() as $field) {
            if ($field->isRequired() && $this->columnFor($field) === null) {
                $errors[] = "Pflichtfeld \"{$field->label()}\" ist keiner CSV-Spalte zugeordnet.";
            }
        }

        return $errors;
    }
}

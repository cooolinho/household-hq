<?php

namespace App\Services\TransactionImport;

use App\Models\Financial\Transaction;

/**
 * Wendet Mapping und Wertumwandlung auf einen CSV-Datensatz an und prüft Pflichtfelder.
 *
 * Es werden ausschließlich die in TransactionImportField definierten Spalten aus der CSV übernommen;
 * Systemspalten (Konto, Benutzer, Hash) setzt der Importer selbst. So ist kein Mass Assignment
 * beliebiger CSV-Spalten möglich.
 */
class TransactionRowValidator
{
    public function __construct(private readonly ValueTransformer $transformer) {}

    public function validate(CsvRecord $record, ImportConfiguration $configuration): RowResult
    {
        $attributes = [];
        $errors = [];

        foreach (TransactionImportField::cases() as $field) {
            $index = $configuration->columnFor($field);
            $attributes[$field->value] = null;

            if ($index === null) {
                continue;
            }

            if (! $record->hasColumn($index)) {
                if ($field->isRequired()) {
                    $errors[] = $this->error($field, ImportProblem::MissingColumn, sprintf(
                        '%s: %s fehlt in dieser Zeile (nur %d Spalten).',
                        $field->label(),
                        CsvReader::columnLabel($index),
                        count($record->values),
                    ));
                }

                continue;
            }

            $raw = $record->value($index);

            if (trim((string) $raw) === '') {
                if ($field->isRequired()) {
                    $errors[] = $this->error($field, ImportProblem::MissingRequired, "{$field->label()}: Pflichtwert fehlt.");
                }

                continue;
            }

            try {
                $attributes[$field->value] = $this->transformer->transform($field, $raw, $configuration->format);
            } catch (InvalidValueException $exception) {
                $errors[] = $this->error($field, $exception->problem, "{$field->label()}: {$exception->getMessage()}");
            }
        }

        if ($errors !== []) {
            return new RowResult($record->line, [], $errors);
        }

        $attributes[Transaction::bank_account_id] = $configuration->bankAccount->id;
        $attributes[Transaction::user_id] = $configuration->userId;
        $attributes[Transaction::hash] = Transaction::createHash($attributes);

        return new RowResult($record->line, $attributes, []);
    }

    /** @return array{field: string, problem: ImportProblem, message: string} */
    private function error(TransactionImportField $field, ImportProblem $problem, string $message): array
    {
        return ['field' => $field->value, 'problem' => $problem, 'message' => $message];
    }
}

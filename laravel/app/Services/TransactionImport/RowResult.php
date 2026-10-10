<?php

namespace App\Services\TransactionImport;

/**
 * Ergebnis der Umwandlung/Validierung eines CSV-Datensatzes.
 */
final readonly class RowResult
{
    /**
     * @param  array<string, mixed>  $attributes  Spaltenwerte für financial_transactions (nur bei gültigem Datensatz)
     * @param  list<array{field: string, problem: ImportProblem, message: string}>  $errors
     */
    public function __construct(
        public int $line,
        public array $attributes,
        public array $errors,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}

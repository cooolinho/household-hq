<?php

namespace App\Services\TransactionImport;

use App\Models\Financial\Transaction;

/**
 * Erkennt bereits importierte Transaktionen über den bestehenden Hash (Transaction::createHash()),
 * auf dem financial_transactions einen Unique-Index hat. Der Hash umfasst Buchungsdatum, Wertstellung,
 * Betrag, Saldo, Auftraggeber, Buchungstext, Verwendungszweck und Benutzer.
 */
class DuplicateDetector
{
    private const int CHUNK_SIZE = 500;

    /**
     * @param  list<string>  $hashes
     * @return array<string, true> bereits vorhandene Hashes
     */
    public function existingHashes(array $hashes): array
    {
        $existing = [];

        foreach (array_chunk(array_values(array_unique($hashes)), self::CHUNK_SIZE) as $chunk) {
            Transaction::query()
                ->whereIn(Transaction::hash, $chunk)
                ->pluck(Transaction::hash)
                ->each(function (string $hash) use (&$existing): void {
                    $existing[$hash] = true;
                });
        }

        return $existing;
    }
}

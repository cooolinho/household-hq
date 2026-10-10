<?php

namespace App\Services\TransactionImport;

use App\Exceptions\TransactionsImportException;
use App\Jobs\Scheduled\CategorizeTransactionsJob;
use App\Jobs\Scheduled\FixedCostTransactionMatchingJob;
use App\Jobs\Scheduled\RecurringTransactionSuggestionDetectionJob;
use App\Jobs\Scheduled\RefreshTransactionStatisticsJob;
use App\Models\Financial\Transaction;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/**
 * Prüft und importiert CSV-Dateien als Transaktionen.
 *
 * Ablauf: CsvReader (streamend) → TransactionRowValidator (Mapping, Umwandlung, Pflichtfelder)
 * → DuplicateDetector (Hash) → Insert in Chunks. Unabhängig von Filament nutz- und testbar.
 */
class TransactionImportService
{
    private const int CHUNK_SIZE = 500;

    public function __construct(
        private readonly CsvReader $reader,
        private readonly TransactionRowValidator $validator,
        private readonly DuplicateDetector $duplicateDetector,
    ) {}

    /**
     * Prüfung ohne Schreibzugriff: was würde beim Import passieren?
     *
     * @throws TransactionsImportException
     */
    public function review(string $path, ImportConfiguration $configuration): ImportReport
    {
        return $this->process($path, $configuration, write: false);
    }

    /**
     * Prüft die Datei und importiert sie, sofern keine blockierenden Fehler vorliegen.
     * Ist der Import nicht möglich, wird der Prüfbericht unverändert (executed = false) zurückgegeben.
     *
     * @throws TransactionsImportException
     */
    public function import(string $path, ImportConfiguration $configuration): ImportReport
    {
        $review = $this->review($path, $configuration);

        if (! $review->canImport($configuration->skipInvalidRows)) {
            return $review;
        }

        $report = DB::transaction(fn () => $this->process($path, $configuration, write: true));

        if ($report->imported > 0) {
            $this->afterImport($configuration);
        }

        return $report;
    }

    /**
     * @throws TransactionsImportException
     */
    private function process(string $path, ImportConfiguration $configuration, bool $write): ImportReport
    {
        $report = new ImportReport;
        $report->mappingErrors = $configuration->mappingErrors();
        $report->executed = $write;

        if ($report->mappingErrors !== []) {
            return $report;
        }

        /** @var array<string, int> $seenHashes Hash => Zeile, für Duplikate innerhalb der Datei */
        $seenHashes = [];
        /** @var list<RowResult> $buffer */
        $buffer = [];

        foreach ($this->reader->records($path, $configuration->format) as $record) {
            if ($record->isEmpty()) {
                $report->emptyLines++;

                continue;
            }

            $report->records++;
            $row = $this->validator->validate($record, $configuration);

            if (! $row->isValid()) {
                $report->addInvalidRow($row->line, $row->errors);

                continue;
            }

            $hash = $row->attributes[Transaction::hash];

            if (isset($seenHashes[$hash])) {
                $report->addDuplicate($row->line, inFile: true);

                continue;
            }

            $seenHashes[$hash] = $row->line;
            $buffer[] = $row;

            if (count($buffer) >= self::CHUNK_SIZE) {
                $this->flush($buffer, $report, $write);
                $buffer = [];
            }
        }

        $this->flush($buffer, $report, $write);

        return $report;
    }

    /**
     * @param  list<RowResult>  $rows
     */
    private function flush(array $rows, ImportReport $report, bool $write): void
    {
        if ($rows === []) {
            return;
        }

        $existing = $this->duplicateDetector->existingHashes(
            array_map(fn (RowResult $row) => $row->attributes[Transaction::hash], $rows),
        );

        $now = now();
        $inserts = [];

        foreach ($rows as $row) {
            if (isset($existing[$row->attributes[Transaction::hash]])) {
                $report->addDuplicate($row->line, inFile: false);

                continue;
            }

            $report->valid++;
            $inserts[] = [
                ...$row->attributes,
                Transaction::created_at => $now,
                Transaction::updated_at => $now,
            ];
        }

        if ($write && $inserts !== []) {
            // insertOrIgnore schützt über den Unique-Index auf hash zusätzlich vor parallelen Importen
            $inserted = Transaction::query()->insertOrIgnore($inserts);
            $report->imported += $inserted;
            $report->duplicatesInDatabase += count($inserts) - $inserted;
            $report->valid -= count($inserts) - $inserted;
        }
    }

    /**
     * Folgeaufgaben wie beim bisherigen Konto-Import: Saldo aktualisieren, kategorisieren, Fixkosten zuordnen.
     */
    private function afterImport(ImportConfiguration $configuration): void
    {
        $configuration->bankAccount->updateBalance();

        // Kategorisierung muss vor dem Fixkosten-Matching laufen, damit die Kategorie-Komponente
        // des Matchings frisch importierte Transaktionen berücksichtigen kann.
        Bus::chain([
            new CategorizeTransactionsJob,
            new FixedCostTransactionMatchingJob,
        ])->dispatch();

        RecurringTransactionSuggestionDetectionJob::dispatch();
        RefreshTransactionStatisticsJob::dispatch();
    }
}

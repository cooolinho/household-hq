<?php

namespace App\Services;

use App\Concerns\TransactionCSVFile;
use App\Exceptions\TransactionsImportException;
use App\Models\CSVImportProfile;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class TransactionsCSVReaderService
{
    private string $delimiter = ';';
    private string $enclosure = '"';
    private string $escape = '\\';
    private ?string $filePath = null;
    private int $offsetHeader = 0;
    private array $mapping = [
        Transaction::date => 0,
        Transaction::value_date => 1,
        Transaction::payer => 2,
        Transaction::description => 3,
        Transaction::purpose => 4,
        Transaction::balance => 5,
        Transaction::balance_currency => 6,
        Transaction::amount => 7,
        Transaction::amount_currency => 8,
    ];
    private array $rows = [];
    private array $header = [];

    /**
     * @throws TransactionsImportException
     */
    public function load(
        string            $filePath,
        ?CSVImportProfile $profile = null,
        string            $sortBy = Transaction::date,
        string            $sortDirection = 'asc'
    ): TransactionCSVFile
    {
        $this->filePath = $filePath;

        if ($profile) {
            $this->setProfile($profile);
        }

        $this->handle();

        return new TransactionCSVFile(
            $this->header,
            collect($this->rows)->sortBy(
                $sortBy,
                SORT_REGULAR,
                strtolower($sortDirection) === 'desc'
            )->values()
        );
    }

    private function setProfile(CSVImportProfile $profile): void
    {
        if (!empty($profile->delimiter)) {
            $this->delimiter = $profile->delimiter;
        }

        if (!empty($profile->enclosure)) {
            $this->enclosure = $profile->enclosure;
        }

        if (!empty($profile->escape)) {
            $this->escape = $profile->escape;
        }

        if (!empty($profile->offset_header)) {
            $this->offsetHeader = $profile->offset_header;
        }

        if (!empty($profile->mapping)) {
            $this->mapping = $profile->mapping;
        }
    }

    /**
     * @throws TransactionsImportException
     */
    private function handle(): void
    {
        $handle = fopen($this->filePath, 'r');

        try {
            $this->loadHeader($handle);
            $this->loadRecords($handle);
        } catch (\Exception $e) {
            throw new TransactionsImportException('Error reading CSV file: ' . $e->getMessage());
        } finally {
            fclose($handle);
        }
    }

    private function loadHeader($handle): void
    {
        $header = fgetcsv(
            $handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );

        if (is_array($header)) {
            $this->header = $header;
        }
    }

    private function loadRecords($handle): void
    {
        $offset = 0;
        while (($line = fgetcsv(
                $handle,
                0,
                $this->delimiter,
                $this->enclosure,
                $this->escape
            )) !== false) {
            if ($offset < $this->offsetHeader) {
                $offset++;
                continue;
            }

            $date = $this->getDate($line);
            $amount = $this->getAmount($line);

            if ($date === null || $amount === null) {
                continue;
            }

            $row = [
                Transaction::date => $date,
                Transaction::amount => $amount,
                Transaction::value_date => $this->getValueDate($line),
                Transaction::payer => $this->getPayer($line),
                Transaction::description => $this->getDescription($line),
                Transaction::purpose => $this->getPurpose($line),
                Transaction::balance => $this->getBalance($line),
                Transaction::balance_currency => $this->getBalanceCurrency($line),
                Transaction::amount_currency => $this->getAmountCurrency($line),
                Transaction::user_id => auth()->id(),
            ];

            $hash = $this->createTransactionHash($row);

            $row[Transaction::hash] = $hash;

            $this->rows[] = $row;
        }
    }

    private function getDate(array $line): ?string
    {
        $date = $line[$this->mapping[Transaction::date]] ?? null;
        if (!$date) {
            return null;
        }

        if (!strtotime($date)) {
            return null;
        }

        return new Carbon($date)->toDateString();
    }

    private function getAmount(array $line): ?float
    {
        return isset($line[$this->mapping[Transaction::amount]]) ? (float)$line[$this->mapping[Transaction::amount]] : null;
    }

    private function getValueDate(array $line): ?string
    {
        $date = $line[$this->mapping[Transaction::value_date]] ?? null;
        if (!$date) {
            return null;
        }

        if (!strtotime($date)) {
            return null;
        }

        return new Carbon($date)->toDateString();
    }

    private function getPayer(array $line): ?string
    {
        return $line[$this->mapping[Transaction::payer]] ?? null;
    }

    private function getDescription(array $line): ?string
    {
        return isset($line[$this->mapping[Transaction::description]]) ? mb_convert_encoding($line[$this->mapping[Transaction::description]], 'UTF-8', 'auto') : null;
    }

    private function getPurpose(array $line): ?string
    {
        return $line[$this->mapping[Transaction::purpose]] ?? null;
    }

    private function getBalance(array $line): ?float
    {
        return isset($line[$this->mapping[Transaction::balance]]) ? (float)$line[$this->mapping[Transaction::balance]] : null;
    }

    private function getBalanceCurrency(array $line): ?string
    {
        return $line[$this->mapping[Transaction::balance_currency]] ?? null;
    }

    private function getAmountCurrency(array $line): ?string
    {
        return $line[$this->mapping[Transaction::amount_currency]] ?? null;
    }

    private function createTransactionHash(array $row): string
    {
        return md5(sprintf(
            '%s|%s|%s|%s|%s|%s|%s|%s',
            $row[Transaction::date],
            $row[Transaction::amount],
            $row[Transaction::value_date],
            $row[Transaction::payer] ?? '',
            $row[Transaction::description] ?? '',
            $row[Transaction::purpose] ?? '',
            $row[Transaction::balance] ?? '',
            $row[Transaction::user_id] ?? ''
        ));
    }
}

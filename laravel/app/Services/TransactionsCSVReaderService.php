<?php

namespace App\Services;

use App\Concerns\TransactionCSVFile;
use App\Exceptions\TransactionsImportException;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use Illuminate\Support\Carbon;

class TransactionsCSVReaderService
{
    private const string AMOUNT_FORMAT_GERMAN = 'de_de';
    private const string AMOUNT_FORMAT_ENGLISH = 'en_us';

    private string $delimiter = ';';
    private string $enclosure = '"';
    private string $escape = '\\';
    private ?string $filePath = null;
    private int $offsetHeader = 0;
    private string $amountFormat = self::AMOUNT_FORMAT_GERMAN;
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
        $this->resetState();
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

        if (!empty($profile->amount_format)) {
            $this->amountFormat = $profile->amount_format;
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
            $this->header = array_map([$this, 'normalizeCsvValue'], $header);
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

            $row[Transaction::hash] = Transaction::createHash($row);

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
        return isset($line[$this->mapping[Transaction::amount]])
            ? $this->parseDecimalValue($line[$this->mapping[Transaction::amount]])
            : null;
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
        return $this->normalizeCsvValue($line[$this->mapping[Transaction::payer]] ?? null);
    }

    private function getDescription(array $line): ?string
    {
        return $this->normalizeCsvValue($line[$this->mapping[Transaction::description]] ?? null);
    }

    private function getPurpose(array $line): ?string
    {
        return $this->normalizeCsvValue($line[$this->mapping[Transaction::purpose]] ?? null);
    }

    private function getBalance(array $line): ?float
    {
        return isset($line[$this->mapping[Transaction::balance]])
            ? $this->parseDecimalValue($line[$this->mapping[Transaction::balance]])
            : null;
    }

    private function getBalanceCurrency(array $line): ?string
    {
        return $this->normalizeCsvValue($line[$this->mapping[Transaction::balance_currency]] ?? null);
    }

    private function getAmountCurrency(array $line): ?string
    {
        return $this->normalizeCsvValue($line[$this->mapping[Transaction::amount_currency]] ?? null);
    }

    private function normalizeCsvValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value === '') {
            return '';
        }

        $stringValue = (string)$value;

        if (mb_check_encoding($stringValue, 'UTF-8')) {
            return $stringValue;
        }

        $normalized = mb_convert_encoding($stringValue, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');

        if ($normalized === false) {
            return $stringValue;
        }

        return $normalized;
    }

    private function parseDecimalValue(mixed $value): ?float
    {
        $normalized = $this->normalizeCsvValue($value);

        if ($normalized === null) {
            return null;
        }

        $normalized = trim(str_replace(' ', '', $normalized));

        if ($normalized === '') {
            return null;
        }

        if (!preg_match($this->getAmountPattern(), $normalized)) {
            return null;
        }

        $separators = $this->getAmountSeparators();
        $normalized = str_replace($separators['thousands'], '', $normalized);
        $normalized = str_replace($separators['decimal'], '.', $normalized);

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float)$normalized;
    }

    private function resetState(): void
    {
        $this->delimiter = ';';
        $this->enclosure = '"';
        $this->escape = '\\';
        $this->filePath = null;
        $this->offsetHeader = 0;
        $this->amountFormat = self::AMOUNT_FORMAT_GERMAN;
        $this->mapping = [
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
        $this->rows = [];
        $this->header = [];
    }

    private function getAmountSeparators(): array
    {
        return match ($this->amountFormat) {
            self::AMOUNT_FORMAT_ENGLISH => [
                'decimal' => '.',
                'thousands' => ',',
            ],
            default => [
                'decimal' => ',',
                'thousands' => '.',
            ],
        };
    }

    private function getAmountPattern(): string
    {
        return match ($this->amountFormat) {
            self::AMOUNT_FORMAT_ENGLISH => '/^[+-]?(?:\d{1,3}(?:,\d{3})*|\d+)(?:\.\d+)?$/',
            default => '/^[+-]?(?:\d{1,3}(?:\.\d{3})*|\d+)(?:,\d+)?$/',
        };
    }

}

<?php

namespace App\Concerns;

use Illuminate\Support\Collection;

readonly class TransactionCSVFile
{
    public function __construct(
        private array      $header,
        private Collection $records
    )
    {
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function getRecords(): Collection
    {
        return $this->records;
    }
}

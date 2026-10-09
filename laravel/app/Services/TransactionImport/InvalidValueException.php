<?php

namespace App\Services\TransactionImport;

use InvalidArgumentException;

/**
 * Ein CSV-Wert lässt sich nicht in den Zieltyp der Transaction-Spalte umwandeln.
 */
class InvalidValueException extends InvalidArgumentException
{
    public function __construct(public readonly ImportProblem $problem, string $message)
    {
        parent::__construct($message);
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FixedCostAmountNotZeroRule implements ValidationRule
{
    /**
     * Run the validation rule.
     * checks if the amount is greater or lesser than 0
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((float)$value === 0.0) {
            $fail('Der Betrag darf nicht 0 sein.');
        }
    }
}

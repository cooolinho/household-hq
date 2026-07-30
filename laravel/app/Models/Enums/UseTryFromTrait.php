<?php

namespace App\Models\Enums;

trait UseTryFromTrait
{
    /**
     * Tries to get the enum case from the given case name.
     *
     * @param string $caseName The name of the enum case.
     * @return static|null The enum case if found, or null if not found.
     */
    public static function tryFrom(string $caseName): null|static
    {
        return array_find(self::cases(), fn($case) => $case->name === $caseName);
    }
}

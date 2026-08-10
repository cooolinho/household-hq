<?php

namespace App\Models\Enums;

enum ImportedEmailAttachmentStatusEnum
{
    use UseEnumOptionsTrait;

    case IMPORTED;
    case SKIPPED;
    case FAILED;

    public function label(): string
    {
        return match ($this) {
            self::IMPORTED => 'Importiert',
            self::SKIPPED => 'Übersprungen',
            self::FAILED => 'Fehlgeschlagen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::IMPORTED => 'success',
            self::SKIPPED => 'warning',
            self::FAILED => 'danger',
        };
    }
}


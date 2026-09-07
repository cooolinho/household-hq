<?php

namespace App\Services\Reminder;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Beschreibt ein Model, das Reminder unterstützt: welche Datums-Properties zur Auswahl
 * stehen und wie sich Empfänger-User, Titel, URL und die für den aktuellen Benutzer
 * auswählbaren Datensätze auflösen lassen.
 */
final readonly class ReminderTarget
{
    /**
     * @param array<string, string> $dateProperties Spalte => Label
     * @param Closure(Model): (User|null) $userResolver
     * @param Closure(Model): string $titleResolver
     * @param Closure(Model): (string|null) $urlResolver
     * @param Closure(): array<int, string> $optionsResolver Datensatz-ID => Label, gescoped auf den aktuellen Benutzer
     */
    public function __construct(
        public string   $modelClass,
        public string   $label,
        public array    $dateProperties,
        private Closure $userResolver,
        private Closure $titleResolver,
        private Closure $urlResolver,
        private Closure $optionsResolver,
    )
    {
    }

    public function resolveUser(Model $model): ?User
    {
        return ($this->userResolver)($model);
    }

    public function resolveTitle(Model $model): string
    {
        return ($this->titleResolver)($model);
    }

    public function resolveUrl(Model $model): ?string
    {
        return ($this->urlResolver)($model);
    }

    /**
     * @return array<int, string>
     */
    public function options(): array
    {
        return ($this->optionsResolver)();
    }

    public function dateLabel(string $property): string
    {
        return $this->dateProperties[$property] ?? $property;
    }
}

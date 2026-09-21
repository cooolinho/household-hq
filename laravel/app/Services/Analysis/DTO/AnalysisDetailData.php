<?php

namespace App\Services\Analysis\DTO;

/**
 * Reiche Daten für die Detailansicht ("Mehr...") einer Auswertungs-Karte.
 *
 * - chart: ['type' => 'pie'|'bar', 'labels' => [...], 'datasets' => [...]]
 * - stats: list of ['label' => string, 'value' => string, 'color' => ?string]
 * - rows: list of associative rows for a detail table
 * - columns: list of ['key' => string, 'label' => string, 'align' => 'left'|'right']
 */
final readonly class AnalysisDetailData
{
    /**
     * @param  array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}|null  $chart
     * @param  list<array{label: string, value: string, color?: string|null}>  $stats
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{key: string, label: string, align?: string}>  $columns
     */
    public function __construct(
        public string $title,
        public ?array $chart = null,
        public array $stats = [],
        public array $rows = [],
        public array $columns = [],
        public string $emptyMessage = 'Keine Daten für den gewählten Zeitraum.',
    ) {}

    public function hasChart(): bool
    {
        return is_array($this->chart)
            && ($this->chart['labels'] ?? []) !== [];
    }

    public function hasRows(): bool
    {
        return $this->rows !== [];
    }
}

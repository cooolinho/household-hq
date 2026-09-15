<?php

namespace App\Services\Analysis\DTO;

use App\Models\Enums\AnalysisModuleEnum;

/**
 * Kompakte Kartendaten für das Grid der Auswertungsseite.
 *
 * - chartType = 'pie' | 'doughnut' | 'bar' | null
 * - list = modulspezifische Zeilen (Budget-/Goal-/Tag-Fortschritt)
 */
final readonly class AnalysisCardData
{
    /**
     * @param  list<string>  $chartLabels
     * @param  list<array<string, mixed>>  $chartDatasets
     * @param  list<array<string, mixed>>  $list
     */
    public function __construct(
        public string $title,
        public AnalysisModuleEnum $module,
        public string $totalLabel,
        public string $totalFormatted,
        public ?string $chartType = null,
        public array $chartLabels = [],
        public array $chartDatasets = [],
        public array $list = [],
        public string $emptyMessage = 'Keine Daten für den gewählten Zeitraum.',
    ) {}

    public function hasChart(): bool
    {
        return $this->chartType !== null && $this->chartLabels !== [];
    }

    public function hasList(): bool
    {
        return $this->list !== [];
    }
}

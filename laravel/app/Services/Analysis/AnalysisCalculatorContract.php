<?php

namespace App\Services\Analysis;

use App\Models\AnalysisCard;
use App\Services\Analysis\DTO\AnalysisCardData;
use App\Services\Analysis\DTO\AnalysisDetailData;

interface AnalysisCalculatorContract
{
    /**
     * Kompakte Kartendaten für das Grid.
     */
    public function card(AnalysisCard $card): AnalysisCardData;

    /**
     * Reiche Daten für die Detailansicht ("Mehr...").
     */
    public function detail(AnalysisCard $card): AnalysisDetailData;
}

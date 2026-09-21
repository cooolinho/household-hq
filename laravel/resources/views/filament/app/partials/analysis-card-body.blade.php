@php
    /** @var \App\Models\AnalysisCard $card */
    /** @var \App\Services\Analysis\DTO\AnalysisCardData $data */
    $hasChart = $data->hasChart();
    $hasList = $data->hasList();
@endphp

<div class="ph-analysis-card__body {{ $hasChart && $hasList ? 'ph-analysis-card__body--split' : '' }}">
    {{-- Left: chart --}}
    @if ($hasChart)
        <div class="ph-analysis-card__chart-wrap">
            @php
                $chartId = 'analysis-card-chart-' . $card->getKey();
                $chartData = [
                    'labels' => $data->chartLabels,
                    'datasets' => $data->chartDatasets,
                ];
                $chartType = $data->chartType;
            @endphp
            <div
                wire:ignore
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                x-data="chart({
                    cachedData: @js($chartData),
                    options: @js($data->chartType === 'bar' ? ['plugins' => ['legend' => ['display' => true]], 'scales' => ['x' => ['stacked' => false], 'y' => ['stacked' => false]]] : (object) []),
                    type: @js($chartType),
                })"
                class="ph-analysis-card__chart"
                data-chart-type="{{ $chartType }}"
            >
                <canvas x-ref="canvas"></canvas>
                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>
        </div>
    @endif

    {{-- Right / below: list (Budgets / Sparziele / Tags top-3) --}}
    @if ($hasList)
        <div class="ph-analysis-card__list-wrap">
            @if ($card->{ \App\Models\AnalysisCard::module }->name === 'BUDGETS')
                @foreach ($data->list as $row)
                    <div class="ph-analysis-card__progress-row">
                        <div class="ph-analysis-card__progress-label">{{ $row['name'] }}</div>
                        <div class="ph-analysis-card__progress-bar">
                            <div class="ph-analysis-card__progress-fill {{ 'ph-analysis-card__progress-fill--' . ($row['status_color'] ?? 'primary') }}" style="width: {{ min(100, max(0, (float)($row['progress'] ?? 0))) }}%"></div>
                        </div>
                        <div class="ph-analysis-card__progress-meta">{{ $row['spent'] ?? '-' }} / {{ $row['limit'] ?? '-' }} ({{ number_format((float)($row['percentage'] ?? 0), 1, ',', '.') }} %)</div>
                    </div>
                @endforeach
            @elseif ($card->{ \App\Models\AnalysisCard::module }->name === 'SAVINGS_GOALS')
                @foreach ($data->list as $row)
                    <div class="ph-analysis-card__progress-row">
                        <div class="ph-analysis-card__progress-label">{{ $row['name'] }}</div>
                        <div class="ph-analysis-card__progress-bar">
                            <div class="ph-analysis-card__progress-fill {{ 'ph-analysis-card__progress-fill--' . ($row['status_color'] ?? 'primary') }}" style="width: {{ min(100, max(0, (float)($row['progress'] ?? 0))) }}%"></div>
                        </div>
                        <div class="ph-analysis-card__progress-meta">{{ $row['current'] ?? '-' }} / {{ $row['target'] ?? '-' }} ({{ number_format((float)($row['percentage'] ?? 0), 1, ',', '.') }} %)</div>
                    </div>
                @endforeach
            @elseif ($card->{ \App\Models\AnalysisCard::module }->name === 'TAGS')
                @foreach ($data->list as $row)
                    <div class="ph-analysis-card__tag-row">
                        <span class="ph-analysis-card__tag-name">{{ $row['name'] }}</span>
                        <span class="ph-analysis-card__tag-expense">{{ $row['expense'] ?? $row['net'] ?? '-' }}</span>
                    </div>
                @endforeach
            @else
                @foreach ($data->list as $row)
                    <div class="ph-analysis-card__list-row">
                        @foreach ($row as $key => $value)
                            <span class="ph-analysis-card__list-cell ph-analysis-card__list-cell--{{ $key }}">{{ $value }}</span>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    @if (! $hasChart && ! $hasList && $data->emptyMessage !== '')
        <p class="ph-analysis-card__empty">{{ $data->emptyMessage }}</p>
    @endif
</div>

@php /** @var \App\Services\Analysis\DTO\AnalysisDetailData $detail */ @endphp
<div class="ph-analysis-detail">
    @if ($detail->stats !== [])
        <div class="ph-analysis-detail__stats">
            @foreach ($detail->stats as $stat)
                <div class="ph-analysis-detail__stat">
                    <div class="ph-analysis-detail__stat-label">{{ $stat['label'] }}</div>
                    <div class="ph-analysis-detail__stat-value {{ isset($stat['color']) ? 'ph-analysis-detail__stat-value--' . $stat['color'] : '' }}">{{ $stat['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($detail->hasChart())
        <div class="ph-analysis-detail__chart-wrap">
            @php
                $detailChartData = [
                    'labels' => $detail->chart['labels'] ?? [],
                    'datasets' => $detail->chart['datasets'] ?? [],
                ];
                $detailChartType = $detail->chart['type'] ?? 'bar';
            @endphp
            <div
                wire:ignore
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                x-data="chart({
                    cachedData: @js($detailChartData),
                    options: @js($detailChartType === 'bar' ? ['plugins' => ['legend' => ['display' => true]], 'scales' => ['x' => ['stacked' => false], 'y' => ['stacked' => false]]] : (object) []),
                    type: @js($detailChartType),
                })"
                class="ph-analysis-detail__chart"
                data-chart-type="{{ $detailChartType }}"
            >
                <canvas x-ref="canvas" style="max-height: 320px"></canvas>
                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>
        </div>
    @endif

    @if ($detail->hasRows())
        <div class="ph-analysis-detail__table-wrap">
            <table class="ph-analysis-detail__table">
                <thead>
                    <tr>
                        @foreach ($detail->columns as $col)
                            <th class="ph-analysis-detail__th {{ isset($col['align']) && $col['align'] === 'right' ? 'ph-analysis-detail__th--right' : '' }}">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detail->rows as $row)
                        <tr>
                            @foreach ($detail->columns as $col)
                                <td class="ph-analysis-detail__td {{ isset($col['align']) && $col['align'] === 'right' ? 'ph-analysis-detail__td--right' : '' }}">{{ $row[$col['key']] ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (! $detail->hasChart() && ! $detail->hasRows())
        <p class="ph-analysis-detail__empty">{{ $detail->emptyMessage }}</p>
    @endif
</div>

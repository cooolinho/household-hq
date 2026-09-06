@php
    /** @var \App\Models\Financial\Budget $record */
    /** @var \App\Services\Budget\BudgetCalculation $calculation */
    /** @var list<array{label: string, start: \Carbon\CarbonImmutable, end: \Carbon\CarbonImmutable, spent: float, limit: float}> $history */

    $status = $calculation->status;
    $currency = $record->currency;
    $money = static fn (float $value): string => number_format($value, 2, ',', '.') . ' ' . $currency;
    $historyMax = max(1.0, max(array_map(static fn (array $entry): float => max($entry['spent'], $entry['limit']), $history)));
@endphp

<div class="ph-budget-details ph-budget-details--{{ $status->color() }}">
    <section class="ph-budget-details__hero">
        <span class="ph-budget-details__icon">
            <x-filament::icon :icon="$record->icon->iconName()"/>
        </span>

        <div class="ph-budget-details__intro">
            <p class="ph-budget-details__eyebrow">
                {{ $record->period->label() }} &middot; {{ $calculation->periodLabel() }}
            </p>
            <h2 class="ph-budget-details__title">{{ $record->name }}</h2>
            @if (filled($record->description))
                <p class="ph-budget-details__description">{{ $record->description }}</p>
            @endif
        </div>

        <span class="ph-budget-details__status">
            <x-filament::icon :icon="$status->icon()"/>
            {{ $status->label() }}
        </span>
    </section>

    <section class="ph-budget-details__progress">
        <div class="ph-budget-details__progress-head">
            <strong>{{ $money($calculation->spent) }}</strong>
            <span>von {{ $money($calculation->limit) }}</span>
            <span class="ph-budget-details__progress-percentage">
                {{ number_format($calculation->percentage, 1, ',', '.') }} %
            </span>
        </div>

        <div
                class="ph-budget-details__bar"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="{{ (int) round($calculation->progressPercentage()) }}"
        >
            <span
                    class="ph-budget-details__bar-fill"
                    style="width: {{ $calculation->progressPercentage() }}%"
            ></span>
        </div>

        <p class="ph-budget-details__range">
            {{ $calculation->periodStart->format('d.m.Y') }} &ndash; {{ $calculation->periodEnd->format('d.m.Y') }}
            &middot; Tag {{ $calculation->daysElapsed }} von {{ $calculation->daysTotal }}
        </p>
    </section>

    <section class="ph-budget-details__metrics">
        <div class="ph-budget-details__metric">
            <span class="ph-budget-details__label">{{ $calculation->isOverspent() ? 'Überschreitung' : 'Verbleibend' }}</span>
            <strong class="ph-budget-details__value">{{ $money(abs($calculation->remaining)) }}</strong>
        </div>
        <div class="ph-budget-details__metric">
            <span class="ph-budget-details__label">Ø pro Tag bisher</span>
            <strong class="ph-budget-details__value">{{ $money($calculation->dailyAverage) }}</strong>
        </div>
        <div class="ph-budget-details__metric">
            <span class="ph-budget-details__label">Tagesbudget für den Rest</span>
            <strong class="ph-budget-details__value">
                {{ $calculation->daysRemaining > 0 ? $money($calculation->dailyAllowance) : '—' }}
            </strong>
        </div>
        <div class="ph-budget-details__metric">
            <span class="ph-budget-details__label">Hochrechnung Periodenende</span>
            <strong class="ph-budget-details__value {{ $calculation->projected > $calculation->limit ? 'ph-budget-details__value--warn' : '' }}">
                {{ $money($calculation->projected) }}
            </strong>
        </div>
        <div class="ph-budget-details__metric">
            <span class="ph-budget-details__label">Voraussichtlich aufgebraucht</span>
            <strong class="ph-budget-details__value">
                {{ $calculation->projectedExceededAt?->format('d.m.Y') ?? ($calculation->isOverspent() ? 'bereits überschritten' : '—') }}
            </strong>
        </div>
        <div class="ph-budget-details__metric">
            <span class="ph-budget-details__label">Schwellwerte</span>
            <strong class="ph-budget-details__value">
                {{ $record->warning_threshold }} % / {{ $record->critical_threshold }} %
            </strong>
        </div>
    </section>

    <section class="ph-budget-details__columns">
        <div class="ph-budget-details__panel">
            <h3 class="ph-budget-details__panel-title">Verknüpfte Kategorien</h3>
            <ul class="ph-budget-details__categories">
                @forelse ($record->transactionCategories as $category)
                    <li>{{ $category->parent?->name ? $category->parent->name . ' › ' : '' }}{{ $category->name }}</li>
                @empty
                    <li class="ph-budget-details__muted">Keine Kategorien verknüpft</li>
                @endforelse
            </ul>
            <p class="ph-budget-details__muted">
                Unterkategorien werden {{ $record->include_subcategories ? 'einbezogen' : 'nicht einbezogen' }}.
            </p>
        </div>

        <div class="ph-budget-details__panel">
            <h3 class="ph-budget-details__panel-title">Verlauf</h3>
            <ul class="ph-budget-details__history">
                @foreach ($history as $entry)
                    @php
                        $width = min(100, (int) round($entry['spent'] / $historyMax * 100));
                        $over = $entry['spent'] > $entry['limit'];
                    @endphp
                    <li class="ph-budget-details__history-row {{ $over ? 'ph-budget-details__history-row--over' : '' }}">
                        <span class="ph-budget-details__history-label">{{ $entry['label'] }}</span>
                        <span class="ph-budget-details__history-bar">
                            <span style="width: {{ $width }}%"></span>
                        </span>
                        <span class="ph-budget-details__history-value">{{ $money($entry['spent']) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="ph-budget-details__channels">
        <span class="ph-budget-details__label">Benachrichtigungen</span>
        <span>
            {{ $record->send_notification ? 'App-Benachrichtigung' : null }}
            {{ $record->send_notification && $record->send_mail ? ' · ' : null }}
            {{ $record->send_mail ? 'E-Mail' : null }}
            {{ !$record->send_notification && !$record->send_mail ? 'deaktiviert' : null }}
        </span>
    </section>
</div>

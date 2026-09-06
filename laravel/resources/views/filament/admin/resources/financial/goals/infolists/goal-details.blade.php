@php
    /** @var \App\Models\Financial\Goal $record */
    /** @var \App\Services\Goal\GoalCalculation $calculation */

    $type = $record->type;
    $currency = $record->currency;
    $money = static fn (float $value): string => number_format($value, 2, ',', '.') . ' ' . $currency;
    $imageUrl = $record->imageUrl();
@endphp

<div class="ph-goal-details ph-goal-details--{{ $calculation->statusColor() }}">
    <section
            class="ph-goal-details__hero {{ $imageUrl ? 'ph-goal-details__hero--photo' : '' }}"
            @if ($imageUrl) style="background-image: url('{{ $imageUrl }}')" @endif
    >
        <span class="ph-goal-details__icon">
            <x-filament::icon :icon="$record->icon->iconName()"/>
        </span>

        <div class="ph-goal-details__intro">
            <p class="ph-goal-details__eyebrow">
                {{ $type->label() }}
                @if ($record->target_date)
                    &middot; Ziel bis {{ $record->target_date->format('d.m.Y') }}
                @endif
            </p>
            <h2 class="ph-goal-details__title">{{ $record->name }}</h2>
            @if (filled($record->description))
                <p class="ph-goal-details__description">{{ $record->description }}</p>
            @endif
        </div>

        <span class="ph-goal-details__status">
            @if ($calculation->isCompleted())
                <x-filament::icon icon="heroicon-o-check-circle"/>
                Erreicht
            @elseif ($calculation->isBehindSchedule())
                <x-filament::icon icon="heroicon-o-exclamation-triangle"/>
                Im Verzug
            @else
                <x-filament::icon icon="heroicon-o-arrow-trending-up"/>
                Auf Kurs
            @endif
        </span>
    </section>

    <section class="ph-goal-details__progress">
        <div class="ph-goal-details__progress-head">
            <strong>{{ $type->currentLabel() }}: {{ $money($calculation->currentAmount) }}</strong>
            <span>{{ $type->remainingLabel() }}: {{ $money($calculation->remaining) }}</span>
            <span class="ph-goal-details__progress-percentage">
                {{ number_format($calculation->percentage, 1, ',', '.') }} %
            </span>
        </div>

        <div
                class="ph-goal-details__bar"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="{{ (int) round($calculation->progressPercentage()) }}"
        >
            <span
                    class="ph-goal-details__bar-fill"
                    style="width: {{ $calculation->progressPercentage() }}%"
            ></span>
        </div>

        <p class="ph-goal-details__range">
            Seit {{ $calculation->startDate->format('d.m.Y') }} &middot; Monat {{ $calculation->monthsElapsed }}
        </p>
    </section>

    <section class="ph-goal-details__metrics">
        <div class="ph-goal-details__metric">
            <span class="ph-goal-details__label">Ø pro Monat</span>
            <strong class="ph-goal-details__value">{{ $money($calculation->monthlyAverage) }}</strong>
        </div>
        <div class="ph-goal-details__metric">
            <span class="ph-goal-details__label">Typische {{ $type->contributionLabel() }}</span>
            <strong class="ph-goal-details__value">
                {{ $calculation->typicalInstallment !== null ? $money($calculation->typicalInstallment) : '—' }}
            </strong>
        </div>
        <div class="ph-goal-details__metric">
            <span class="ph-goal-details__label">Voraussichtlich fertig</span>
            <strong class="ph-goal-details__value">
                {{ $calculation->isCompleted()
                    ? 'Erreicht'
                    : ($calculation->projectedCompletionAt?->format('d.m.Y') ?? 'Noch keine Prognose') }}
            </strong>
        </div>
        @if ($record->type === \App\Models\Enums\GoalTypeEnum::DEBT_PAYOFF)
            <div class="ph-goal-details__metric">
                <span class="ph-goal-details__label">Verbleibende Raten</span>
                <strong class="ph-goal-details__value">
                    {{ $calculation->remainingInstallments !== null ? $calculation->remainingInstallments : '—' }}
                </strong>
            </div>
        @endif
        @if ($record->target_date)
            <div class="ph-goal-details__metric">
                <span class="ph-goal-details__label">Nötige Monatsrate</span>
                <strong class="ph-goal-details__value {{ $calculation->isBehindSchedule() ? 'ph-goal-details__value--warn' : '' }}">
                    {{ $calculation->requiredMonthlyRate !== null ? $money($calculation->requiredMonthlyRate) : '—' }}
                </strong>
            </div>
            <div class="ph-goal-details__metric">
                <span class="ph-goal-details__label">Zieldatum erreichbar?</span>
                <strong class="ph-goal-details__value {{ $calculation->isBehindSchedule() ? 'ph-goal-details__value--warn' : '' }}">
                    {{ $calculation->isCompleted() ? 'Erreicht' : ($calculation->willReachTargetDate() ? 'Ja' : 'Voraussichtlich nicht') }}
                </strong>
            </div>
        @endif
    </section>

    <section class="ph-goal-details__columns">
        <div class="ph-goal-details__panel">
            <h3 class="ph-goal-details__panel-title">Verknüpfte Kategorien</h3>
            <ul class="ph-goal-details__categories">
                @forelse ($record->transactionCategories as $category)
                    <li>{{ $category->parent?->name ? $category->parent->name . ' › ' : '' }}{{ $category->name }}</li>
                @empty
                    <li class="ph-goal-details__muted">Keine Kategorien verknüpft – rein manuelle Führung.</li>
                @endforelse
            </ul>
            <p class="ph-goal-details__muted">
                Unterkategorien werden {{ $record->include_subcategories ? 'einbezogen' : 'nicht einbezogen' }}.
                Zählrichtung: {{ $record->direction->label() }}.
            </p>
        </div>

        <div class="ph-goal-details__panel">
            <h3 class="ph-goal-details__panel-title">Beiträge</h3>
            <p class="ph-goal-details__muted">
                Aus Transaktionen: {{ $money($calculation->transactionContributed) }}
            </p>
            <p class="ph-goal-details__muted">
                Manuelle Einzahlungen: {{ $money($calculation->manualContributed) }}
            </p>
        </div>
    </section>
</div>

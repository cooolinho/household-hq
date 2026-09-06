@php
    use App\Filament\Admin\Resources\Financial\Budgets\BudgetResource;

    /** @var list<\App\Services\Budget\BudgetCalculation> $calculations */
    $calculations = $this->budgetCalculations();
@endphp

<x-filament-panels::page>
    @if ($calculations === [])
        <div class="ph-budget-empty">
            <x-filament::icon
                    icon="heroicon-o-chart-pie"
                    class="ph-budget-empty__icon"
            />
            <h2 class="ph-budget-empty__title">Noch keine Budgets angelegt</h2>
            <p class="ph-budget-empty__text">
                Lege ein Budget an und verknüpfe es mit Transaktionskategorien, um deine Ausgaben
                im laufenden Zeitraum zu verfolgen.
            </p>
            @if ($this->hasInactiveBudgets())
                <p class="ph-budget-empty__text">
                    Es existieren inaktive Budgets, die hier nicht angezeigt werden.
                </p>
            @endif
        </div>
    @else
        <div class="ph-budget-grid">
            @foreach ($calculations as $calculation)
                @php
                    $budget = $calculation->budget;
                    $status = $calculation->status;
                    $currency = $budget->currency;
                    $money = static fn (float $value): string => number_format($value, 2, ',', '.') . ' ' . $currency;
                @endphp

                <a
                        href="{{ BudgetResource::getUrl('view', ['record' => $budget->getKey()]) }}"
                        class="ph-budget-card ph-budget-card--{{ $status->color() }}"
                        aria-labelledby="ph-budget-card-title-{{ $budget->getKey() }}"
                >
                    <header class="ph-budget-card__header">
                        <span class="ph-budget-card__icon">
                            <x-filament::icon :icon="$budget->icon->iconName()"/>
                        </span>

                        <div class="ph-budget-card__heading">
                            <h2 id="ph-budget-card-title-{{ $budget->getKey() }}" class="ph-budget-card__title">
                                {{ $budget->name }}
                            </h2>
                            <p class="ph-budget-card__period">{{ $calculation->periodLabel() }}</p>
                        </div>

                        <span class="ph-budget-card__badge">
                            {{ number_format($calculation->percentage, 0, ',', '.') }} %
                        </span>
                    </header>

                    <div
                            class="ph-budget-card__bar"
                            role="progressbar"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="{{ (int) round($calculation->progressPercentage()) }}"
                            aria-label="{{ $status->label() }}"
                    >
                        <span
                                class="ph-budget-card__bar-fill"
                                style="width: {{ $calculation->progressPercentage() }}%"
                        ></span>
                    </div>

                    <dl class="ph-budget-card__figures">
                        <div>
                            <dt>Verbraucht</dt>
                            <dd>{{ $money($calculation->spent) }}</dd>
                        </div>
                        <div>
                            <dt>Limit</dt>
                            <dd>{{ $money($calculation->limit) }}</dd>
                        </div>
                        <div>
                            <dt>{{ $calculation->isOverspent() ? 'Überschreitung' : 'Verbleibend' }}</dt>
                            <dd class="ph-budget-card__remaining">{{ $money(abs($calculation->remaining)) }}</dd>
                        </div>
                    </dl>

                    <footer class="ph-budget-card__footer">
                        <span class="ph-budget-card__status">
                            <x-filament::icon :icon="$status->icon()"/>
                            {{ $status->label() }}
                        </span>

                        <span class="ph-budget-card__forecast">
                            @if ($calculation->isOverspent())
                                {{ $money(abs($calculation->remaining)) }} über dem Limit
                            @elseif ($calculation->projectedExceededAt !== null)
                                Voraussichtlich aufgebraucht am
                                {{ $calculation->projectedExceededAt->format('d.m.Y') }}
                            @elseif ($calculation->isProjectedToExceed())
                                Hochrechnung: {{ $money($calculation->projected) }}
                            @elseif ($calculation->daysRemaining > 0)
                                Noch {{ $money($calculation->dailyAllowance) }} pro Tag
                            @else
                                Zeitraum abgeschlossen
                            @endif
                        </span>
                    </footer>
                </a>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

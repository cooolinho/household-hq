@php
    use App\Filament\App\Resources\Financial\Goals\GoalResource;

    /** @var list<\App\Services\Goal\GoalCalculation> $calculations */
    $calculations = $this->goalCalculations();
@endphp

<x-filament-panels::page>
    @if ($calculations === [])
        <div class="ph-goal-empty">
            <x-filament::icon
                    icon="heroicon-o-flag"
                    class="ph-goal-empty__icon"
            />
            <h2 class="ph-goal-empty__title">Noch keine Ziele angelegt</h2>
            <p class="ph-goal-empty__text">
                Lege ein Ziel an, um eine Abzahlung oder ein Sparvorhaben zu verfolgen – optional
                verknüpft mit Transaktionskategorien und ergänzt um manuelle Einzahlungen.
            </p>
            @if ($this->hasInactiveGoals())
                <p class="ph-goal-empty__text">
                    Es existieren inaktive Ziele, die hier nicht angezeigt werden.
                </p>
            @endif
        </div>
    @else
        <div class="ph-goal-grid">
            @foreach ($calculations as $calculation)
                @php
                    $goal = $calculation->goal;
                    $type = $goal->type;
                    $currency = $goal->currency;
                    $money = static fn (float $value): string => number_format($value, 2, ',', '.') . ' ' . $currency;
                @endphp

                <a
                        href="{{ GoalResource::getUrl('view', ['record' => $goal->getKey()]) }}"
                        class="ph-goal-card ph-goal-card--{{ $calculation->statusColor() }}"
                        aria-labelledby="ph-goal-card-title-{{ $goal->getKey() }}"
                >
                    @if ($goal->imageUrl())
                        <div
                                class="ph-goal-card__thumbnail"
                                style="background-image: url('{{ $goal->imageUrl() }}')"
                        ></div>
                    @endif

                    <header class="ph-goal-card__header">
                        <span class="ph-goal-card__icon">
                            <x-filament::icon :icon="$goal->icon->iconName()"/>
                        </span>

                        <div class="ph-goal-card__heading">
                            <h2 id="ph-goal-card-title-{{ $goal->getKey() }}" class="ph-goal-card__title">
                                {{ $goal->name }}
                            </h2>
                            <p class="ph-goal-card__type">{{ $type->label() }}</p>
                        </div>

                        <span class="ph-goal-card__badge">
                            {{ number_format($calculation->percentage, 0, ',', '.') }} %
                        </span>
                    </header>

                    <div
                            class="ph-goal-card__bar"
                            role="progressbar"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="{{ (int) round($calculation->progressPercentage()) }}"
                    >
                        <span
                                class="ph-goal-card__bar-fill"
                                style="width: {{ $calculation->progressPercentage() }}%"
                        ></span>
                    </div>

                    <dl class="ph-goal-card__figures">
                        <div>
                            <dt>{{ $type->currentLabel() }}</dt>
                            <dd>{{ $money($calculation->currentAmount) }}</dd>
                        </div>
                        <div>
                            <dt>{{ $type->remainingLabel() }}</dt>
                            <dd class="ph-goal-card__remaining">{{ $money($calculation->remaining) }}</dd>
                        </div>
                    </dl>

                    <footer class="ph-goal-card__footer">
                        <span>
                            @if ($calculation->isCompleted())
                                Ziel erreicht
                            @elseif ($calculation->projectedCompletionAt !== null)
                                Voraussichtlich fertig am {{ $calculation->projectedCompletionAt->format('d.m.Y') }}
                            @else
                                Noch keine Prognose möglich
                            @endif
                        </span>
                    </footer>
                </a>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

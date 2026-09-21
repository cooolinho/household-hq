<x-filament-panels::page class="ph-analysis-page">
    <div class="ph-analysis-grid">
        @forelse ($this->getCards() as $card)
            @php
                $data = $this->getCardData($card);
                $widthClass = match ($card->{ \App\Models\AnalysisCard::width }) {
                    \App\Models\AnalysisCard::WIDTH_SMALL => 'ph-analysis-card--small',
                    'full' => 'ph-analysis-card--full',
                    '4' => 'ph-analysis-card--full-grid',
                    default => 'ph-analysis-card--half',
                };
            @endphp

            <div class="ph-analysis-card {{ $widthClass }}" wire:key="analysis-card-{{ $card->getKey() }}">
                {{-- Card header --}}
                <div class="ph-analysis-card__header">
                    <div class="ph-analysis-card__title-wrap">
                        <h3 class="ph-analysis-card__title">{{ $card->{ \App\Models\AnalysisCard::title } }}</h3>
                        <span class="ph-analysis-card__badge">{{ $card->{ \App\Models\AnalysisCard::module }->label() }}</span>
                    </div>
                    <div class="ph-analysis-card__actions">
                        <button type="button" class="ph-analysis-card__action" wire:click="mountAction('detailCard', {cardId: {{ $card->getKey() }}})">Mehr...</button>
                    </div>
                </div>

                {{-- Card body --}}
                @include('filament.app.partials.analysis-card-body', ['card' => $card, 'data' => $data])

                {{-- Card footer --}}
                <div class="ph-analysis-card__footer">
                    <div class="ph-analysis-card__total">
                        <span class="ph-analysis-card__total-label">{{ $data->totalLabel }}</span>
                        <span class="ph-analysis-card__total-value">{{ $data->totalFormatted }}</span>
                    </div>
                    <div class="ph-analysis-card__footer-actions">
                        <button type="button" class="ph-analysis-card__footer-btn" wire:click="mountAction('editCard', {cardId: {{ $card->getKey() }}})">Bearbeiten</button>
                        <button type="button" class="ph-analysis-card__footer-btn ph-analysis-card__footer-btn--danger" wire:click="mountAction('deleteCard', {cardId: {{ $card->getKey() }}})">Löschen</button>
                        <span class="ph-analysis-card__sort-sep"></span>
                        <button type="button" class="ph-analysis-card__footer-btn ph-analysis-card__footer-btn--icon" wire:click="mountAction('moveCardUp', {cardId: {{ $card->getKey() }}})" title="Nach oben">▲</button>
                        <button type="button" class="ph-analysis-card__footer-btn ph-analysis-card__footer-btn--icon" wire:click="mountAction('moveCardDown', {cardId: {{ $card->getKey() }}})" title="Nach unten">▼</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="ph-analysis-empty">
                <p class="ph-analysis-empty__text">Noch keine Auswertungs-Karten angelegt.</p>
                <p class="ph-analysis-empty__hint">Klicke auf „Karte hinzufügen", um deine erste Auswertung zu erstellen.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>

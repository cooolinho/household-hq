@php
    use App\Filament\Admin\Resources\Documents\DocumentResource;
    use App\Filament\Admin\Resources\Financial\Transactions\TransactionResource;
    use App\Models\Enums\FixedCostCategoryEnum;
    use App\Models\Enums\FixedCostEndsModeEnum;
    use App\Models\Enums\FixedCostIntervalEnum;
    use App\Models\Financial\Transaction;

    /** @var \App\Models\Financial\FixedCost $fixedCost */
    $fixedCost = $this->getRecord();

    $amount = (float) $fixedCost->amount;
    $amountSign = $amount < 0 ? '-' : '+';
    $amountClass = $amount < 0 ? 'is-negative' : 'is-positive';
    $amountFormatted = number_format(abs($amount), 2, ',', '.');

    $categoryLabel = FixedCostCategoryEnum::tryFrom((string) $fixedCost->category)?->label() ?? (string) $fixedCost->category;
    $intervalLabel = FixedCostIntervalEnum::tryFrom((string) $fixedCost->interval)?->label() ?? (string) $fixedCost->interval;
    $endsModeLabel = FixedCostEndsModeEnum::tryFrom((string) $fixedCost->ends_mode)?->label() ?? (string) $fixedCost->ends_mode;

    $documents = $fixedCost->documents()->get();
    $transactions = $fixedCost->transactions()
        ->orderByDesc(Transaction::date)
        ->get();

    $documentCreateUrl = DocumentResource::getUrl(DocumentResource::PAGE_CREATE_FOR_FIXED_COST, [
        'owner' => $fixedCost->id,
    ]);
@endphp

<x-filament-panels::page>
    <div class="fixed-cost-view">
        <section class="fc-hero-card">
            <div class="fc-hero-headline">
                <p class="fc-eyebrow">Fixkosten</p>
                <h1 class="fc-name">{{ $fixedCost->name }}</h1>
            </div>

            <div class="fc-main-metrics">
                <div class="fc-amount {{ $amountClass }}">
                    <span class="fc-amount-label">Betrag</span>
                    <span class="fc-amount-value">
                        {{ $amountSign }} {{ $amountFormatted }}
                        <small>{{ $transactions->first()?->amount_currency ?? 'EUR' }}</small>
                    </span>
                </div>

                <div class="fc-category">
                    <span class="fc-category-label">Kategorie</span>
                    <span class="fc-category-value">{{ $categoryLabel }}</span>
                </div>
            </div>
        </section>

        <section class="fc-meta-grid">
            <article>
                <span>Intervall</span>
                <strong>{{ $intervalLabel }}</strong>
            </article>
            <article>
                <span>Endmodus</span>
                <strong>{{ $endsModeLabel }}</strong>
            </article>
            <article>
                <span>Naechste Buchung</span>
                <strong>{{ $fixedCost->next_booking_date?->format('d.m.Y') ?? '-' }}</strong>
            </article>
            <article>
                <span>Erstellt</span>
                <strong>{{ $fixedCost->created_at?->format('d.m.Y H:i') ?? '-' }}</strong>
            </article>
        </section>

        <section class="fc-transactions" data-fixed-cost-transactions>
            <button
                    class="fc-transactions-toggle"
                    type="button"
                    data-fixed-cost-toggle
                    aria-controls="fc-transactions-panel"
                    aria-expanded="false"
            >
                <span>Verknuepfte Dokumente ({{ $documents->count() }})</span>
                <span class="fc-chevron" data-fixed-cost-chevron>▼</span>
            </button>

            <div id="fc-transactions-panel" class="fc-transactions-panel" data-fixed-cost-panel hidden>
                @foreach($documents as $document)
                    @php
                        /** @var \App\Models\Document $document */
                        $documentViewUrl = DocumentResource::getUrl('view', ['record' => $document]);
                    @endphp

                    <article class="fc-transaction-row">
                        <div class="fc-transaction-main">
                            <h3>{{ $document->filename ?: basename($document->path ?: '-') }}</h3>
                            <p>
                                Typ: {{ $document->type ?: '-' }}
                                | MIME: {{ $document->mime_type ?: '-' }}
                                |
                                Groesse: {{ $document->file_size ? number_format($document->file_size / 1024, 1, ',', '.') . ' KB' : '-' }}
                            </p>
                            <a href="{{ $documentViewUrl }}" class="iv-transaction-link">Details ansehen</a>
                        </div>
                        <div class="fc-transaction-side">
                            <time datetime="{{ $document->created_at?->format('Y-m-d') }}">
                                {{ $document->created_at?->format('d.m.Y') ?? '-' }}
                            </time>
                        </div>
                    </article>
                @endforeach

                @if($documents->isEmpty())
                    <div class="iv-empty-state">
                        <p class="fc-empty">Es sind noch keine Dokumente verknuepft.</p>
                        <a href="{{ $documentCreateUrl }}" class="fc-transaction-link">Dokument anlegen</a>
                    </div>
                @else
                    <a href="{{ $documentCreateUrl }}" class="fc-transaction-link">Dokument anlegen</a>
                @endif
            </div>
        </section>

        <section class="fc-transactions" data-fixed-cost-transactions>
            <button
                    class="fc-transactions-toggle"
                    type="button"
                    data-fixed-cost-toggle
                    aria-controls="fc-transactions-panel"
                    aria-expanded="false"
            >
                <span>Verknuepfte Transaktionen ({{ $transactions->count() }})</span>
                <span class="fc-chevron" data-fixed-cost-chevron>▼</span>
            </button>

            <div id="fc-transactions-panel" class="fc-transactions-panel" data-fixed-cost-panel hidden>
                @if($transactions->isEmpty())
                    <p class="fc-empty">Es sind noch keine Transaktionen verknuepft.</p>
                @else
                    @foreach($transactions as $transaction)
                        @php
                            /** @var \App\Models\Financial\Transaction $transaction */
                            $transactionAmount = (float) $transaction->amount;
                            $txAmountClass = $transactionAmount < 0 ? 'is-negative' : 'is-positive';
                            $transactionViewUrl = TransactionResource::getUrl('view', ['record' => $transaction]);
                        @endphp

                        <article class="fc-transaction-row">
                            <div class="fc-transaction-main">
                                <h3>{{ $transaction->purpose ?: '-' }}</h3>
                                <p>{{ $transaction->payer ?: '-' }}</p>
                                <a href="{{ $transactionViewUrl }}" class="fc-transaction-link">Details ansehen</a>
                            </div>
                            <div class="fc-transaction-side">
                                <time datetime="{{ $transaction->date?->format('Y-m-d') }}">
                                    {{ $transaction->date?->format('d.m.Y') ?? '-' }}
                                </time>
                                <strong class="{{ $txAmountClass }}">
                                    {{ $transactionAmount < 0 ? '-' : '+' }} {{ number_format(abs($transactionAmount), 2, ',', '.') }} {{ $transaction->amount_currency }}
                                </strong>
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>
        </section>
    </div>

    @vite('resources/js/filament/admin/fixed-cost-view.js')
</x-filament-panels::page>


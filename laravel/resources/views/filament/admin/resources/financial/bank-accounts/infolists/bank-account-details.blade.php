@php
    /** @var \App\Models\Financial\BankAccount $record */
    $balance = $record->balance;
    $balanceClass = $balance === null
        ? 'bank-card-is-neutral'
        : ((float) $balance < 0 ? 'bank-card-is-negative' : 'bank-card-is-positive');
    $balanceFormatted = $balance === null
        ? '-'
        : number_format((float) $balance, 2, ',', '.') . ' €';
    $typeLabel = $record->type?->label() ?? '-';
@endphp

<div class="bank-card-wrapper">
    <section
            class="bank-card"
            style="--bank-card-bg-light: url('{{ asset('images/bank-card-bg-light.svg') }}'); --bank-card-bg-dark: url('{{ asset('images/bank-card-bg.svg') }}');"
            aria-labelledby="bank-card-name-{{ $record->getKey() }}"
    >
        <header class="bank-card-header">
            <span class="bank-card-logo">
                {{-- Platzhalter – später durch hochgeladenes Bank-Icon ersetzen --}}
                <img
                        src="{{ asset('images/bank-icon-placeholder.svg') }}"
                        alt="Bank-Icon (Platzhalter)"
                />
            </span>
            <div class="bank-card-institute">
                <p class="bank-card-institute-name">{{ $record->bank_name ?: 'Bank nicht hinterlegt' }}</p>
                <p class="bank-card-institute-sub">Bankkonto &middot; {{ $typeLabel }}</p>
            </div>
        </header>

        <div class="bank-card-divider" role="presentation"></div>

        <div class="bank-card-body">
            <h2 id="bank-card-name-{{ $record->getKey() }}" class="bank-card-account-name">
                {{ $record->name }}
            </h2>

            <div class="bank-card-balance {{ $balanceClass }}">
                <span class="bank-card-label">Kontostand</span>
                <strong class="bank-card-balance-value">{{ $balanceFormatted }}</strong>
                <span class="bank-card-balance-date">
                    Stand: {{ $record->balance_date?->format('d.m.Y') ?? '-' }}
                </span>
            </div>

            <div class="bank-card-numbers">
                <div class="bank-card-number">
                    <span class="bank-card-label">IBAN</span>
                    <span class="bank-card-mono">{{ $record->iban ?: '-' }}</span>
                </div>
                <div class="bank-card-number">
                    <span class="bank-card-label">BIC</span>
                    <span class="bank-card-mono">{{ $record->bic ?: '-' }}</span>
                </div>
            </div>
        </div>

        <footer class="bank-card-footer">
            <div>
                <span class="bank-card-label">Kontoinhaber</span>
                <strong>{{ $record->account_holder ?: '-' }}</strong>
            </div>
            <div>
                <span class="bank-card-label">Erstellt</span>
                <strong>{{ $record->created_at?->format('d.m.Y H:i') ?? '-' }}</strong>
            </div>
            <div>
                <span class="bank-card-label">Aktualisiert</span>
                <strong>{{ $record->updated_at?->format('d.m.Y H:i') ?? '-' }}</strong>
            </div>
        </footer>
    </section>
</div>

{{-- Bank statement style transaction preview --}}
<div class="ph-transaction-statement">
    <div class="ph-transaction-statement__header">
        <div class="ph-transaction-statement__dates">
            <div class="ph-transaction-statement__date-item">
                <span class="ph-transaction-statement__date-label">Buchungsdatum</span>
                <span class="ph-transaction-statement__date-value">{{ $record->date->format('d.m.Y') }}</span>
            </div>
            @if($record->value_date && $record->value_date->format('d.m.Y') !== $record->date->format('d.m.Y'))
                <div class="ph-transaction-statement__date-item">
                    <span class="ph-transaction-statement__date-label">Wertstellung</span>
                    <span class="ph-transaction-statement__date-value">{{ $record->value_date->format('d.m.Y') }}</span>
                </div>
            @endif
        </div>
        <div class="ph-transaction-statement__amount {{ $record->amount >= 0 ? 'ph-transaction-statement__amount--positive' : 'ph-transaction-statement__amount--negative' }}">
            {{ number_format($record->amount, 2, ',', '.') }}&nbsp;{{ $record->amount_currency }}
        </div>
    </div>

    <div class="ph-transaction-statement__body">
        @if($record->payer)
            <div class="ph-transaction-statement__row">
                <span class="ph-transaction-statement__label">Auftraggeber</span>
                <span class="ph-transaction-statement__value">{{ $record->payer }}</span>
            </div>
        @endif

        @if($record->purpose)
            <div class="ph-transaction-statement__row">
                <span class="ph-transaction-statement__label">Verwendungszweck</span>
                <span class="ph-transaction-statement__value">{{ $record->purpose }}</span>
            </div>
        @endif

        @if($record->description)
            <div class="ph-transaction-statement__row">
                <span class="ph-transaction-statement__label">Beschreibung</span>
                <span class="ph-transaction-statement__value">{{ $record->description }}</span>
            </div>
        @endif

        <div class="ph-transaction-statement__row">
            <span class="ph-transaction-statement__label">Kontostand nach Buchung</span>
            <span class="ph-transaction-statement__value">{{ number_format($record->balance, 2, ',', '.') }}&nbsp;{{ $record->balance_currency }}</span>
        </div>
    </div>
</div>

@php
    $transaction = $preview['transaction'] ?? [];
    $transactionSegments = $preview['transaction_segments'] ?? [];
    $criteria = $preview['criteria'] ?? [];
    $amount = $transaction['amount'] ?? null;
    $currency = $transaction['amount_currency'] ?? 'EUR';
    $amountText = $amount !== null ? number_format((float) $amount, 2, ',', '.') : null;
    $amountSegments = $transactionSegments['amount'] ?? ($amountText !== null ? [['text' => $amountText, 'matched' => false]] : []);
    $currencySegments = $transactionSegments['amount_currency'] ?? [['text' => $currency, 'matched' => false]];
@endphp

@if($preview !== [])
    <div class="ph-transaction-statement ph-system-rule-preview">
        <div class="ph-transaction-statement__header">
            <div class="ph-transaction-statement__dates">
                <div class="ph-transaction-statement__date-item">
                    <span class="ph-transaction-statement__date-label">Systemregel #{{ $preview['rule_id'] }}</span>
                    <span class="ph-transaction-statement__date-value">
                        {{ $preview['active'] ? 'Aktiv' : 'Systemseitig deaktiviert' }}
                    </span>
                </div>
                <div class="ph-transaction-statement__date-item">
                    <span class="ph-transaction-statement__date-label">Kriterien</span>
                    <span class="ph-transaction-statement__date-value">
                        {{ $preview['operator'] }} - {{ $preview['operator_label'] }}
                    </span>
                </div>
            </div>

            @if($amountText !== null)
                <div class="ph-transaction-statement__amount {{ $amount >= 0 ? 'ph-transaction-statement__amount--positive' : 'ph-transaction-statement__amount--negative' }}">
                    @foreach($amountSegments as $segment)
                        @if($segment['matched'])
                            <mark class="ph-system-rule-preview__match">{{ $segment['text'] }}</mark>
                        @else
                            {{ $segment['text'] }}
                        @endif
                    @endforeach
                    &nbsp;
                    @foreach($currencySegments as $segment)
                        @if($segment['matched'])
                            <mark class="ph-system-rule-preview__match">{{ $segment['text'] }}</mark>
                        @else
                            {{ $segment['text'] }}
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="ph-transaction-statement__body">
            <div class="ph-system-rule-preview__example-label">Beispieltransaktion</div>

            @if($criteria !== [])
                <div class="ph-system-rule-preview__legend">
                    <span class="ph-system-rule-preview__legend-marker" aria-hidden="true"></span>
                    <span>Hervorgehobene Werte stimmen mit dem Kriterium überein.</span>
                </div>
            @endif

            @if(filled($transaction['date'] ?? null))
                @php
                    $dateSegments = $transactionSegments['date'] ?? [['text' => $transaction['date'], 'matched' => false]];
                @endphp
                <div class="ph-transaction-statement__row">
                    <span class="ph-transaction-statement__label">Buchungsdatum</span>
                    <span class="ph-transaction-statement__value">
                        @foreach($dateSegments as $segment)
                            @if($segment['matched'])
                                <mark class="ph-system-rule-preview__match">{{ $segment['text'] }}</mark>
                            @else
                                {{ $segment['text'] }}
                            @endif
                        @endforeach
                    </span>
                </div>
            @endif

            @foreach([
                'payer' => 'Auftraggeber',
                'purpose' => 'Verwendungszweck',
                'description' => 'Beschreibung',
                'amount_currency' => 'Währung',
            ] as $field => $label)
                @if(filled($transaction[$field] ?? null))
                    @php
                        $fieldSegments = $transactionSegments[$field] ?? [['text' => $transaction[$field], 'matched' => false]];
                    @endphp
                    <div class="ph-transaction-statement__row">
                        <span class="ph-transaction-statement__label">{{ $label }}</span>
                        <span class="ph-transaction-statement__value">
                            @foreach($fieldSegments as $segment)
                                @if($segment['matched'])
                                    <mark class="ph-system-rule-preview__match">{{ $segment['text'] }}</mark>
                                @else
                                    {{ $segment['text'] }}
                                @endif
                            @endforeach
                        </span>
                    </div>
                @endif
            @endforeach

            @if($criteria !== [])
                <div class="ph-system-rule-preview__criteria">
                    <div class="ph-system-rule-preview__criteria-heading">Regelkriterien</div>

                    @foreach($criteria as $criterion)
                        <div class="ph-system-rule-preview__criterion">
                            <span class="ph-transaction-statement__label">{{ $criterion['field_label'] }}</span>
                            <span class="ph-transaction-statement__value">
                                {{ $criterion['operator_label'] }} "
                                @foreach($criterion['value_segments'] as $segment)
                                    @if($segment['matched'])
                                        <mark class="ph-system-rule-preview__match">{{ $segment['text'] }}</mark>
                                    @else
                                        {{ $segment['text'] }}
                                    @endif
                                @endforeach
                                "
                                @if($criterion['operator'] === 'between' && filled($criterion['value_secondary']))
                                    bis "
                                    @foreach($criterion['value_secondary_segments'] as $segment)
                                        @if($segment['matched'])
                                            <mark class="ph-system-rule-preview__match">{{ $segment['text'] }}</mark>
                                        @else
                                            {{ $segment['text'] }}
                                        @endif
                                    @endforeach
                                    "
                                @endif
                                @if($criterion['case_sensitive'])
                                    <span class="ph-system-rule-preview__case-note">(Groß-/Kleinschreibung beachten)</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif

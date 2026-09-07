@php
    use App\Filament\App\Resources\Documents\DocumentResource;
    use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
    use App\Filament\App\Resources\Financial\Transactions\TransactionResource;
    use App\Models\Enums\FixedCostEndsModeEnum;
    use App\Models\Enums\FixedCostIntervalEnum;
    use App\Models\Enums\FixedCostIntervalUnitEnum;
    use App\Models\Financial\Transaction;
    use App\Models\Reminder;
    use App\Models\ReminderSchedule;

    /** @var \App\Models\Financial\FixedCost $fixedCost */
    $fixedCost = $this->getRecord();

    $amount = (float) $fixedCost->amount;
    $amountSign = $amount < 0 ? '-' : '+';
    $amountClass = $amount < 0 ? 'is-negative' : 'is-positive';
    $amountFormatted = number_format(abs($amount), 2, ',', '.');

    $categoryLabel = $fixedCost->category?->name ?? 'Nicht kategorisiert';
    $intervalLabel = FixedCostIntervalEnum::tryFrom((string) $fixedCost->interval)?->label() ?? (string) $fixedCost->interval;
    if ($fixedCost->interval === FixedCostIntervalEnum::CUSTOM->name) {
        $unitLabel = FixedCostIntervalUnitEnum::tryFrom((string) $fixedCost->custom_interval_unit)?->label();
        $intervalLabel = $fixedCost->custom_interval_value !== null && $unitLabel !== null
            ? sprintf('Alle %d %s', $fixedCost->custom_interval_value, $unitLabel)
            : FixedCostIntervalEnum::CUSTOM->label();
    }
    $endsModeLabel = FixedCostEndsModeEnum::tryFrom((string) $fixedCost->ends_mode)?->label() ?? (string) $fixedCost->ends_mode;

    $documents = $fixedCost->documents()->get();
    $transactions = $fixedCost->transactions()
        ->orderByDesc(Transaction::date)
        ->get();
    $reminders = $fixedCost->reminders()
        ->with(Reminder::has_many_schedules)
        ->orderBy(Reminder::name)
        ->get();

    $reminderRows = $reminders->flatMap(function (Reminder $reminder): \Illuminate\Support\Collection {
        return $reminder->{Reminder::has_many_schedules}->map(function (ReminderSchedule $schedule) use ($reminder): array {
            return [
                'name' => $reminder->{Reminder::name},
                'schedule_label' => $schedule->label(),
                'channels_label' => Reminder::channelsLabel((bool) $reminder->{Reminder::send_mail}, (bool) $reminder->{Reminder::send_notification}),
                'enabled' => (bool) $reminder->{Reminder::enabled} && (bool) $schedule->{ReminderSchedule::enabled},
                'next_due_at' => $schedule->{ReminderSchedule::next_due_at}?->format('d.m.Y H:i') ?? '-',
                'last_sent_at' => $schedule->{ReminderSchedule::last_sent_at}?->format('d.m.Y H:i') ?? '-',
            ];
        });
    });
    $reminderCount = $reminderRows->count();
    $activeReminderCount = $reminderRows->where('enabled', true)->count();

    $documentCreateUrl = DocumentResource::getUrl(DocumentResource::PAGE_CREATE_FOR_FIXED_COST, [
        'owner' => $fixedCost->id,
    ]);
    $fixedCostEditUrl = FixedCostResource::getUrl('edit', ['record' => $fixedCost, 'relation' => 1]);
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

        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Reminder</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $activeReminderCount }} von {{ $reminderCount }} Reminder aktiv.
                    </p>
                </div>
                <a href="{{ $fixedCostEditUrl }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                    Reminder verwalten
                </a>
            </div>

            @if($reminderRows->isEmpty())
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Noch keine Reminder konfiguriert.</p>
            @else
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach($reminderRows as $reminder)
                        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $reminder['enabled'] ? 'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $reminder['enabled'] ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                                <span class="inline-flex rounded-full bg-primary-100 px-2 py-1 text-xs font-medium text-primary-800 dark:bg-primary-500/20 dark:text-primary-300">
                                    {{ $reminder['schedule_label'] }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $reminder['name'] }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                <strong>Nächste Erinnerung:</strong> {{ $reminder['next_due_at'] }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                <strong>Kanäle:</strong> {{ $reminder['channels_label'] }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Zuletzt gesendet am {{ $reminder['last_sent_at'] }}
                            </p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    @vite('resources/js/filament/app/fixed-cost-view.js')
</x-filament-panels::page>

@php
    use App\Filament\App\Resources\Documents\DocumentResource;
    use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
    use App\Filament\App\Resources\Financial\Insurances\RelationManagers\DocumentsRelationManager;
    use App\Models\Document;
    use App\Models\Enums\FixedCostEndsModeEnum;
    use App\Models\Enums\FixedCostIntervalEnum;
    use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
    use App\Models\Enums\InsuranceMoveNotificationStatusEnum;
    use App\Models\Financial\FixedCost;

    /** @var \App\Models\Financial\Insurance $insurance */
    $insurance = $this->getRecord();

    $enumLabelByName = static function (string $enumClass, ?string $enumName): string {
        if (!$enumName) {
            return '-';
        }

        foreach ($enumClass::cases() as $case) {
            if ($case->name === $enumName) {
                return $case->label();
            }
        }

        return $enumName;
    };

    $categoryLabel = $insurance->category?->name ?? '-';
    $categoryGroupLabel = $insurance->category?->group ?? '-';
    $moveChannelLabel = $enumLabelByName(InsuranceMoveNotificationChannelEnum::class, $insurance->move_notification_channel);
    $moveStatusLabel = $enumLabelByName(InsuranceMoveNotificationStatusEnum::class, $insurance->move_notification_status);

    $documents = $insurance->documents()->get();
    $fixedCosts = $insurance->fixedCosts()->with(FixedCost::belongs_to_category)->get();

    $documentCreateUrl = DocumentResource::getUrl(DocumentResource::PAGE_CREATE_FOR_INSURANCE, [
        'owner' => $insurance->id,
    ]);

    $fixedCostCreateUrl = FixedCostResource::getUrl('create');

    $address = collect([
        $insurance->address_line_1,
        $insurance->address_line_2,
        trim(($insurance->address_zip ?: '') . ' ' . ($insurance->address_city ?: '')),
        $insurance->address_country,
    ])->filter()->implode(', ');
@endphp

<x-filament-panels::page>
    <div class="insurance-view">
        <section class="iv-hero-card">
            <div class="iv-hero-headline">
                <p class="iv-eyebrow">Versicherung</p>
                <h1 class="iv-name">{{ $insurance->name }}</h1>
            </div>

            <div class="iv-main-metrics">
                <div class="iv-type">
                    <span class="iv-type-label">Kategorie</span>
                    <span class="iv-type-value">{{ $categoryLabel }}</span>
                </div>

                <div class="iv-company">
                    <span class="iv-company-label">Anbieter</span>
                    <span class="iv-company-value">{{ $insurance->company ?: '-' }}</span>
                </div>
            </div>
        </section>

        <section class="iv-meta-grid">
            <article>
                <span>Gruppe</span>
                <strong>{{ $categoryGroupLabel }}</strong>
            </article>
            <article>
                <span>Versicherungsnummer</span>
                <strong>{{ $insurance->number ?: '-' }}</strong>
            </article>
            <article>
                <span>Startdatum</span>
                <strong>{{ $insurance->start_date?->format('d.m.Y') ?? '-' }}</strong>
            </article>
            <article>
                <span>Enddatum</span>
                <strong>{{ $insurance->end_date?->format('d.m.Y') ?? '-' }}</strong>
            </article>
            <article>
                <span>Kontaktperson</span>
                <strong>{{ $insurance->contact_person ?: '-' }}</strong>
            </article>
            <article>
                <span>Telefon</span>
                <strong>{{ $insurance->phone ?: '-' }}</strong>
            </article>
            <article>
                <span>E-Mail</span>
                <strong>{{ $insurance->email ?: '-' }}</strong>
            </article>
            <article>
                <span>Umzug mitgeteilt am</span>
                <strong>{{ $insurance->move_notified_at?->format('d.m.Y H:i') ?? '-' }}</strong>
            </article>
            <article>
                <span>Umzug Kanal</span>
                <strong>{{ $moveChannelLabel ?: '-' }}</strong>
            </article>
            <article>
                <span>Umzug Status</span>
                <strong>{{ $moveStatusLabel ?: '-' }}</strong>
            </article>
            <article class="iv-meta-grid-wide">
                <span>Umzug Notiz</span>
                <strong>{{ $insurance->move_notification_note ?: '-' }}</strong>
            </article>
            <article class="iv-meta-grid-wide">
                <span>Adresse</span>
                <strong>{{ $address ?: '-' }}</strong>
            </article>
            <article>
                <span>Erstellt</span>
                <strong>{{ $insurance->created_at?->format('d.m.Y H:i') ?? '-' }}</strong>
            </article>
        </section>

        <section class="iv-collapsible" data-insurance-collapsible>
            <button
                    class="iv-collapsible-toggle"
                    type="button"
                    data-insurance-toggle
                    aria-controls="iv-documents-panel"
                    aria-expanded="false"
            >
                <span>Verknuepfte Dokumente ({{ $documents->count() }})</span>
                <span class="iv-chevron" data-insurance-chevron>▼</span>
            </button>

            <div id="iv-documents-panel" class="iv-collapsible-panel" data-insurance-panel hidden>
                @foreach($documents as $document)
                    @php
                        /** @var \App\Models\Document $document */
                        $documentViewUrl = DocumentResource::getUrl('view', ['record' => $document]);
                    @endphp

                    <article class="iv-row">
                        <div class="iv-row-main">
                            <h3>{{ $document->filename ?: basename($document->path ?: '-') }}</h3>
                            <p>
                                Typ: {{ $document->type ?: '-' }}
                                | MIME: {{ $document->mime_type ?: '-' }}
                                |
                                Groesse: {{ $document->file_size ? number_format($document->file_size / 1024, 1, ',', '.') . ' KB' : '-' }}
                            </p>
                            <a href="{{ $documentViewUrl }}" class="iv-row-link">Details ansehen</a>
                        </div>
                        <div class="iv-row-side">
                            <time datetime="{{ $document->created_at?->format('Y-m-d') }}">
                                {{ $document->created_at?->format('d.m.Y') ?? '-' }}
                            </time>
                        </div>
                    </article>
                @endforeach

                @if($documents->isEmpty())
                    <div class="iv-empty-state">
                        <p class="iv-empty">Es sind noch keine Dokumente verknuepft.</p>
                        <a href="{{ $documentCreateUrl }}" class="iv-action-link">Dokument anlegen</a>
                    </div>
                @else
                    <a href="{{ $documentCreateUrl }}" class="iv-action-link">Dokument anlegen</a>
                @endif
            </div>
        </section>

        <section class="iv-collapsible" data-insurance-collapsible>
            <button
                    class="iv-collapsible-toggle"
                    type="button"
                    data-insurance-toggle
                    aria-controls="iv-fixed-costs-panel"
                    aria-expanded="false"
            >
                <span>Verknuepfte Fixkosten ({{ $fixedCosts->count() }})</span>
                <span class="iv-chevron" data-insurance-chevron>▼</span>
            </button>

            <div id="iv-fixed-costs-panel" class="iv-collapsible-panel" data-insurance-panel hidden>
                @if($fixedCosts->isEmpty())
                    <div class="iv-empty-state">
                        <p class="iv-empty">Es sind noch keine Fixkosten verknuepft.</p>
                        <a href="{{ $fixedCostCreateUrl }}" class="iv-action-link">Fixkosten anlegen</a>
                    </div>
                @else
                    @foreach($fixedCosts as $fixedCost)
                        @php
                            /** @var \App\Models\Financial\FixedCost $fixedCost */
                            $fixedCostAmount = (float) $fixedCost->amount;
                            $amountSign = $fixedCostAmount < 0 ? '-' : '+';
                            $amountClass = $fixedCostAmount < 0 ? 'is-negative' : 'is-positive';
                            $fixedCostViewUrl = FixedCostResource::getViewUrl($fixedCost->id);

                            $categoryLabel = $fixedCost->category?->name ?? 'Nicht kategorisiert';
                            $intervalLabel = $enumLabelByName(FixedCostIntervalEnum::class, (string) $fixedCost->interval);
                            $endsModeLabel = $enumLabelByName(FixedCostEndsModeEnum::class, (string) $fixedCost->ends_mode);
                        @endphp

                        <article class="iv-row">
                            <div class="iv-row-main">
                                <h3>{{ $fixedCost->name }}</h3>
                                <p>
                                    Kategorie: {{ $categoryLabel ?: '-' }}
                                    | Intervall: {{ $intervalLabel ?: '-' }}
                                    | Endmodus: {{ $endsModeLabel ?: '-' }}
                                </p>
                                <a href="{{ $fixedCostViewUrl }}" class="iv-row-link">Details ansehen</a>
                            </div>
                            <div class="iv-row-side">
                                <time datetime="{{ $fixedCost->next_booking_date?->format('Y-m-d') }}">
                                    Naechste Buchung: {{ $fixedCost->next_booking_date?->format('d.m.Y') ?? '-' }}
                                </time>
                                <strong class="{{ $amountClass }}">
                                    {{ $amountSign }} {{ number_format(abs($fixedCostAmount), 2, ',', '.') }} EUR
                                </strong>
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>
        </section>
    </div>

    @vite('resources/js/filament/app/insurance-view.js')
</x-filament-panels::page>


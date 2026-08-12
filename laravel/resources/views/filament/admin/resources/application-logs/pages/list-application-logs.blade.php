@php
    use App\Filament\Admin\Resources\ApplicationLogs\ApplicationLogResource;
    use Illuminate\Support\Str;

    $logs = $this->applicationLogs;
    $eventOptions = $this->eventOptions;
    $levelOptions = $this->levelOptions;

    $levelStyles = static function (string $level): array {
        return match (strtolower($level)) {
            'error', 'critical', 'alert', 'emergency' => [
                'badge' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-500/10 dark:text-red-200',
                'card' => 'border-red-200/80 dark:border-red-900/60',
                'dot' => 'bg-red-500',
            ],
            'warning' => [
                'badge' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-200',
                'card' => 'border-amber-200/80 dark:border-amber-900/60',
                'dot' => 'bg-amber-500',
            ],
            'notice', 'info' => [
                'badge' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/60 dark:bg-sky-500/10 dark:text-sky-200',
                'card' => 'border-sky-200/80 dark:border-sky-900/60',
                'dot' => 'bg-sky-500',
            ],
            default => [
                'badge' => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
                'card' => 'border-gray-200 dark:border-gray-700',
                'dot' => 'bg-primary-500',
            ],
        };
    };
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-gray-100 dark:border-gray-800 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Application Logs</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Vertikale Timeline mit Filter- und Suchfunktion. Jeder Logeintrag führt direkt zur
                        Detailansicht.
                    </p>
                </div>

                <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-arrow-path"
                        wire:click="resetFilters"
                >
                    Filter zurücksetzen
                </x-filament::button>
            </div>

            <div class="grid gap-4 p-5 lg:grid-cols-12">
                <label class="space-y-2 lg:col-span-4">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Suche</span>
                    <input
                            type="search"
                            wire:model.live.debounce.400ms="search"
                            placeholder="Event, Message, Kanal oder Benutzer"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 p-3"
                    >
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Event</span>
                    <select
                            wire:model.live="eventFilter"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 p-3"
                    >
                        <option value="all">Alle Events</option>
                        @foreach($eventOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Level</span>
                    <select
                            wire:model.live="levelFilter"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 p-3"
                    >
                        <option value="all">Alle Levels</option>
                        @foreach($levelOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-2 sm:col-span-1 lg:col-span-1">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Von</span>
                    <input
                            type="date"
                            wire:model.live="fromDate"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 p-3"
                    >
                </label>

                <label class="space-y-2 sm:col-span-1 lg:col-span-1">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Bis</span>
                    <input
                            type="date"
                            wire:model.live="untilDate"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 p-3"
                    >
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
            <div class="flex flex-col gap-2 border-b border-gray-100 dark:border-gray-800 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Timeline</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $logs->total() }} Einträge gefunden
                    </p>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Die Karten wechseln abwechselnd links und rechts entlang der vertikalen Achse.
                </p>
            </div>

            @if($logs->count() === 0)
                <div class="p-8 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Keine Application Logs für die aktuellen Filter
                        gefunden.</p>
                </div>
            @else
                <div class="relative px-4 py-6 sm:px-5 lg:px-10">
                    <div class="absolute bottom-0 left-4 top-0 w-px bg-gray-200 dark:bg-gray-700 lg:left-1/2"></div>

                    <div class="space-y-8">
                        @foreach($logs as $index => $log)
                            @php
                                $isLeft = $index % 2 === 0;
                                $styles = $levelStyles((string) $log->level);
                                $viewUrl = ApplicationLogResource::getUrl('view', ['record' => $log]);
                                $messagePreview = Str::limit((string) $log->message, 220);
                            @endphp

                            <article class="relative pl-10 lg:grid lg:grid-cols-9 lg:items-start lg:gap-x-6 lg:pl-0">
                                <div class="absolute left-4 top-6 z-10 h-3.5 w-3.5 -translate-x-1/2 rounded-full border-4 border-white dark:border-gray-900 {{ $styles['dot'] }} lg:left-1/2"></div>

                                <div class="lg:col-span-4 {{ $isLeft ? 'lg:col-start-1' : 'lg:col-start-6' }}">
                                    <a
                                            href="{{ $viewUrl }}"
                                            class="group block rounded-xl border bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-950 {{ $styles['card'] }}"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <div class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold uppercase tracking-wide {{ $styles['badge'] }}">
                                                    {{ $log->level }}
                                                </div>
                                                <h4 class="mt-3 text-base font-semibold text-gray-900 transition group-hover:text-primary-600 dark:text-gray-100 dark:group-hover:text-primary-300">
                                                    {{ $log->event }}
                                                </h4>
                                            </div>

                                            <time class="shrink-0 text-xs font-medium text-gray-400 dark:text-gray-500">
                                                {{ $log->occurred_at?->format('d.m.Y H:i:s') }}
                                            </time>
                                        </div>

                                        <p class="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                            {{ $messagePreview }}
                                        </p>

                                        <div class="mt-4 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-800">
                                                Kanal: {{ $log->channel }}
                                            </span>
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-800">
                                                Benutzer: {{ $log->user?->email ?? '-' }}
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>


<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm divide-y divide-gray-100 dark:divide-gray-800">
            <div class="p-5">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $record->event }}
                </h2>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                    {{ $record->message }}
                </p>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800">
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Level
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $record->level }}</dd>
                </div>
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Kanal
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $record->channel }}</dd>
                </div>
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Zeitpunkt
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $record->occurred_at?->format('d.m.Y H:i:s') }}</dd>
                </div>
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Benutzer
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $record->user?->email ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-800 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Context (JSON)</h3>
                <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                        x-data="{}"
                        x-on:click="navigator.clipboard?.writeText($el.closest('[data-context-viewer]').querySelector('[data-json-output]').textContent || '')"
                >
                    Kopieren
                </button>
            </div>

            <div class="p-4" data-context-viewer>
                @if(!empty($prettyContext))
                    <pre data-json-output
                         class="overflow-auto rounded-lg bg-gray-50 dark:bg-gray-950 p-4 text-xs leading-5 text-gray-800 dark:text-gray-200">{{ $prettyContext }}</pre>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kein Context gespeichert.</p>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>


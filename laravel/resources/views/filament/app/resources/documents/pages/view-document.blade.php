<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Metadaten-Karte --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm divide-y divide-gray-100 dark:divide-gray-800">

            {{-- Datei-Header --}}
            <div class="flex items-start gap-4 p-5">
                {{-- Datei-Icon je nach Typ --}}
                <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                    @if($isImage)
                        <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                    @elseif($isPdf)
                        <svg class="w-7 h-7 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    @else
                        <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                        {{ $document->filename ?? 'Dokument' }}
                    </h2>
                    @if($document->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $document->description }}</p>
                    @endif
                    <div class="flex flex-wrap gap-2 mt-2 text-xs">
                        @if($fileExtension)
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase font-mono font-medium">
                                .{{ $fileExtension }}
                            </span>
                        @endif
                        @if($document->mime_type)
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono">
                                {{ $document->mime_type }}
                            </span>
                        @endif
                        @if($document->file_size)
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                {{ number_format($document->file_size / 1024, 2) }} KB
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Metadaten-Zeilen --}}
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800">
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Typ
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $document->type ?? '–' }}</dd>
                </div>
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Verknüpft mit
                    </dt>
                    <dd class="text-sm">
                        @if($linkedOwners->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($linkedOwners as $linkedOwner)
                                    @if(!empty($linkedOwner['url']))
                                        <a href="{{ $linkedOwner['url'] }}"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-medium hover:underline">
                                            {{ $linkedOwner['label'] }}
                                        </a>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-medium">
                                            {{ $linkedOwner['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">Nicht verknüpft</span>
                        @endif
                    </dd>
                </div>
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Sortierung
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $document->sort ?? '–' }}</dd>
                </div>
                <div class="px-5 py-4 sm:border-t sm:border-gray-100 sm:dark:border-gray-800">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Erstellt am
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $document->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                </div>
                <div class="px-5 py-4 sm:border-t sm:border-gray-100 sm:dark:border-gray-800">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Zuletzt geändert
                    </dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200">{{ $document->updated_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                </div>
                <div class="px-5 py-4 sm:border-t sm:border-gray-100 sm:dark:border-gray-800">
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">
                        Speicherpfad
                    </dt>
                    <dd class="text-sm text-gray-500 dark:text-gray-400 font-mono truncate"
                        title="{{ $document->path }}">
                        {{ $document->path ?? '–' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Vorschau-Bereich --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
            @if($canPreview && $fileUrl)
                @if($isImage)
                    <div class="flex items-center justify-center p-6 bg-gray-50 dark:bg-gray-800">
                        <img src="{{ $fileUrl }}" alt="{{ $document->filename }}"
                             class="max-w-full max-h-[75vh] object-contain rounded"/>
                    </div>
                @elseif($isPdf)
                    <div class="w-full" style="height: 80vh; min-height: 650px;">
                        <iframe src="{{ $fileUrl }}#toolbar=1&navpanes=1&scrollbar=1" class="w-full h-full border-0"
                                title="{{ $document->filename }}"></iframe>
                    </div>
                @elseif($isText && $textContent)
                    <div class="p-6 bg-white dark:bg-gray-900 overflow-auto" style="max-height: 75vh;">
                        <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-mono leading-relaxed">{{ $textContent }}</pre>
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center p-16 bg-gray-50 dark:bg-gray-800">
                    <svg class="w-20 h-20 text-gray-300 dark:text-gray-600 mb-4" xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <p class="text-base text-gray-500 dark:text-gray-400 mb-6 text-center">Vorschau für diesen Dateityp
                        nicht verfügbar</p>
                    <a href="{{ route('app.documents.download', $document->id) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium text-sm">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Datei herunterladen
                    </a>
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>


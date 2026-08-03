{{-- Step 3: Dokument-Metadaten --}}
@php
    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $availableTags */
    $availableTags = $this->getAvailableTags();
@endphp

<div class="space-y-5">

    {{-- Beschreibung --}}
    <div>
        <label for="pdf-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Beschreibung
        </label>
        <textarea
                id="pdf-description"
                wire:model.live.debounce.400ms="description"
                rows="3"
                placeholder="Optionale Beschreibung des Dokuments…"
                class="mt-1 px-3 py-2 w-full rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                   placeholder-gray-400 dark:placeholder-gray-500
                   focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent
                   transition"
        ></textarea>
        @error('description')
        <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Dateiname aus Beschreibung (nur sichtbar, wenn Beschreibung gesetzt) --}}
    @if(!empty($this->description))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-4 space-y-3">
            <label class="flex items-start gap-3 cursor-pointer">
                <input
                        type="checkbox"
                        wire:model.live="useDescriptionAsFilename"
                        id="use-desc-filename"
                        class="mt-0.5 rounded border-gray-300 dark:border-gray-600
                           text-primary-600 focus:ring-primary-500"
                />
                <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Dateiname aus Beschreibung ableiten
                    </span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Die Beschreibung wird in einen dateisystemtauglichen Namen umgewandelt
                        (Kleinbuchstaben, keine Leerzeichen, keine Umlaute, keine Sonderzeichen).
                    </p>
                </div>
            </label>

            @if($this->useDescriptionAsFilename)
                @php $preview = $this->getFilenamePreview(); @endphp
                @if($preview)
                    <div class="pl-7 space-y-1.5">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Vorschau Dateiname:</p>
                        <code class="block text-xs font-mono bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded px-3 py-2 text-gray-800 dark:text-gray-200 break-all">
                            {{ $preview }}
                        </code>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            <code class="font-mono text-gray-500">{zeitstempel}</code>
                            wird beim Generieren durch das aktuelle Datum und die Uhrzeit ersetzt
                            (Format: <code class="font-mono text-gray-500">JJJJMMTT_HHMMSS</code>).
                        </p>
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- Tags --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Tags
        </label>

        @if($availableTags->isEmpty())
            <p class="text-sm text-gray-400 dark:text-gray-500 italic">
                Keine Tags vorhanden.
            </p>
        @else
            <select
                    wire:model="tags"
                    multiple
                    size="{{ min($availableTags->count(), 8) }}"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600
                       bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                       focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
                @foreach($availableTags as $tag)
                    <option
                            value="{{ $tag->id }}"
                            class="px-3 py-1.5"
                    >{{ $tag->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                Mehrere Tags mit <kbd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">Strg</kbd>
                / <kbd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">⌘</kbd> + Klick auswählen.
            </p>
        @endif
    </div>

    {{-- Zusammenfassung --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 space-y-2 text-sm">
        <p class="font-medium text-gray-700 dark:text-gray-300">Zusammenfassung</p>
        <dl class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex gap-2">
                <dt class="shrink-0 font-medium">Seiten:</dt>
                <dd>{{ count($this->orderedImages) }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="shrink-0 font-medium">Dateiname:</dt>
                <dd class="font-mono break-all">
                    @if($this->useDescriptionAsFilename && !empty($this->description))
                        {{ $this->getFilenamePreview() }}
                    @else
                        scan_{zeitstempel}.pdf
                    @endif
                </dd>
            </div>
        </dl>
    </div>

</div>


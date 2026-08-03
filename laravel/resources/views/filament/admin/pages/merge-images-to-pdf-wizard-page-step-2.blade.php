{{-- Step 2: Reihenfolge anpassen --}}
<div class="space-y-4">

    @if(empty($this->orderedImages))
        {{-- Leer-Zustand: visuelle Warnung --}}
        <div class="rounded-xl border-2 border-dashed border-danger-300 dark:border-danger-600 bg-danger-50 dark:bg-danger-500/10 p-8 text-center space-y-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto text-danger-400" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <p class="text-sm font-semibold text-danger-700 dark:text-danger-300">Keine Bilder vorhanden</p>
            <p class="text-xs text-danger-600 dark:text-danger-400">
                Bitte gehe zurück zu Schritt 1 und lade mindestens ein Bild hoch.
            </p>
        </div>
        @error('orderedImages')
        <p class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
        @enderror

    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Ziehe die Bilder per Drag&amp;Drop oder nutze die Pfeilschaltflächen.
            Mit dem &times;-Button kann ein Bild entfernt werden.
        </p>

        {{-- Sortierliste --}}
        <div
                x-data="{ dragging: null }"
                class="space-y-2"
        >
            @foreach($this->orderedImages as $index => $img)
                <div
                        wire:key="img-{{ $img['filename'] }}"
                        draggable="true"
                        x-on:dragstart="dragging = {{ $index }}; $event.dataTransfer.effectAllowed = 'move'"
                        x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                        x-on:dragenter.prevent
                        x-on:drop.prevent="
                        if (dragging !== null && dragging !== {{ $index }}) {
                            $wire.call('reorder', dragging, {{ $index }});
                            dragging = null;
                        }
                    "
                        x-on:dragend="dragging = null"
                        :class="{
                        'opacity-40 ring-2 ring-inset ring-primary-500': dragging === {{ $index }},
                        'ring-2 ring-inset ring-primary-300 dark:ring-primary-700': dragging !== null && dragging !== {{ $index }}
                    }"
                        class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-900 p-3 cursor-move select-none transition-all"
                >
                    {{-- Drag-Handle --}}
                    <div class="shrink-0 text-gray-300 dark:text-gray-600 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 11.001 4.001A2 2 0 017 2zm0 6a2 2 0 11.001 4.001A2 2 0 017 8zm0 6a2 2 0 11.001 4.001A2 2 0 017 14zm6-12a2 2 0 11.001 4.001A2 2 0 0113 2zm0 6a2 2 0 11.001 4.001A2 2 0 0113 8zm0 6a2 2 0 11.001 4.001A2 2 0 0113 14z"/>
                        </svg>
                    </div>

                    {{-- Vorschau-Thumbnail --}}
                    <div class="shrink-0 w-14 h-14 overflow-hidden rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <img
                                src="{{ route('admin.scan-temp-preview', ['filename' => $img['filename']]) }}"
                                alt="{{ $img['name'] }}"
                                class="max-w-full max-h-full object-contain"
                                loading="lazy"
                                draggable="false"
                        />
                    </div>

                    {{-- Seiteninfo & Name --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ $img['name'] }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Seite {{ $index + 1 }} von {{ count($this->orderedImages) }}
                        </p>
                    </div>

                    {{-- Steuerelemente --}}
                    <div class="flex items-center gap-1 shrink-0">
                        {{-- Nach oben --}}
                        <button
                                type="button"
                                wire:click="moveUp({{ $index }})"
                                {{ $index === 0 ? 'disabled' : '' }}
                                class="p-1.5 rounded text-gray-500 dark:text-gray-400
                                   hover:bg-gray-100 dark:hover:bg-gray-800
                                   disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                title="Nach oben"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>

                        {{-- Nach unten --}}
                        <button
                                type="button"
                                wire:click="moveDown({{ $index }})"
                                {{ $index === count($this->orderedImages) - 1 ? 'disabled' : '' }}
                                class="p-1.5 rounded text-gray-500 dark:text-gray-400
                                   hover:bg-gray-100 dark:hover:bg-gray-800
                                   disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                title="Nach unten"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Entfernen --}}
                        <button
                                type="button"
                                wire:click="removeImage({{ $index }})"
                                wire:confirm="Dieses Bild aus der Liste entfernen?"
                                class="p-1.5 rounded text-danger-500 dark:text-danger-400
                                   hover:bg-danger-50 dark:hover:bg-danger-500/10 transition-colors"
                                title="Entfernen"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-xs text-gray-400 dark:text-gray-500">
            {{ count($this->orderedImages) }} Bild(er) &bull; {{ count($this->orderedImages) }} PDF-Seite(n)
        </p>
    @endif

</div>


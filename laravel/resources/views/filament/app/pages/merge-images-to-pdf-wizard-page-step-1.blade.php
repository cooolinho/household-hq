{{-- Step 1: Bilder hochladen --}}
<div class="space-y-5">

    {{-- Hinweis, wenn bereits Bilder verarbeitet wurden --}}
    @if(!empty($this->orderedImages))
        <div class="flex items-center gap-3 rounded-lg border border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-500/10 px-4 py-3 text-sm text-success-700 dark:text-success-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>
                <strong>{{ count($this->orderedImages) }} Bild(er)</strong> bereits hochgeladen.
                Weitere Bilder können unten hinzugefügt werden.
            </span>
        </div>
    @endif

    {{-- Upload-Bereich --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Bilder auswählen
            <span class="font-normal text-gray-400 dark:text-gray-500">(JPG, JPEG, PNG – max. 20 MB je Datei)</span>
        </label>

        <label
                x-data="{ dragOver: false }"
                x-on:dragover.prevent="dragOver = true"
                x-on:dragleave.prevent="dragOver = false"
                x-on:drop.prevent="$refs.dropInput.files = $event.dataTransfer.files; $refs.dropInput.dispatchEvent(new Event('change', { bubbles: true })); dragOver = false;"
                :class="{
                    'border-primary-500 bg-primary-50 dark:bg-primary-500/20': dragOver,
                    'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50': !dragOver,
                }"
                class="flex flex-col items-center justify-center w-full h-40 cursor-pointer
                   rounded-xl border-2 border-dashed
                   hover:border-primary-400 dark:hover:border-primary-500
                   transition-colors"
        >
            <div class="flex flex-col items-center gap-2 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Bilder hier ablegen oder <span class="text-primary-600 dark:text-primary-400 font-medium">durchsuchen</span>
                </p>
            </div>

            <input
                    id="drop-input"
                    x-ref="dropInput"
                    type="file"
                    wire:model="uploadedImages"
                    multiple
                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                    class="hidden"
            />
        </label>

        {{-- Lade-Indikator --}}
        <div wire:loading wire:target="uploadedImages"
             class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <svg class="animate-spin w-4 h-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            Bilder werden hochgeladen&hellip;
        </div>

        {{-- Validierungsfehler --}}
        @error('uploadedImages.*')
        <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
        @enderror
        @error('orderedImages')
        <p class="mt-2 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Ausgewählte neue Dateien (noch nicht verarbeitet) --}}
    @if(!empty($this->uploadedImages))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($this->uploadedImages as $i => $file)
                <div wire:key="upload-{{ $i }}"
                     class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-gray-400" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                </div>
            @endforeach
        </div>
    @endif

</div>


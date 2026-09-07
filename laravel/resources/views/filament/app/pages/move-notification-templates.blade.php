<x-filament-panels::page>
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Platzhalter</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Folgende Platzhalter kannst du in Betreff/Text verwenden. Sie werden beim Versand ersetzt.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($this->placeholders as $placeholder)
                    <code class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 text-xs">{{ '{' . '{' . $placeholder . '}' . '}' }}</code>
                @endforeach
            </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <article
                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">E-Mail-Template</h3>

                <div>
                    <label class="block text-sm">Betreff</label>
                    <input type="text" wire:model="emailSubject"
                           class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
                    @error('emailSubject') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm">Text</label>
                    <textarea rows="16" wire:model="emailBody"
                              class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    @error('emailBody') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
            </article>

            <article
                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Brief-Template</h3>

                <div>
                    <label class="block text-sm">Betreff</label>
                    <input type="text" wire:model="letterSubject"
                           class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
                    @error('letterSubject') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm">Text</label>
                    <textarea rows="16" wire:model="letterBody"
                              class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    @error('letterBody') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
            </article>
        </section>

        <section class="flex items-center justify-end gap-2">
            <x-filament::button color="gray" wire:click="resetDefaults" icon="heroicon-o-arrow-path">
                Standard wiederherstellen
            </x-filament::button>
            <x-filament::button wire:click="save" icon="heroicon-o-check">
                Templates speichern
            </x-filament::button>
        </section>
    </div>
</x-filament-panels::page>


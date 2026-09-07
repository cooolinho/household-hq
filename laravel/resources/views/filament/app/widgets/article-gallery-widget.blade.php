<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <span class="flex items-center gap-2">
                <x-heroicon-o-photo class="h-5 w-5 text-primary-500"/>
                Bildergalerie
            </span>
        </x-slot>

        <div
                x-data="{ selectedImage: null }"
                @keydown.escape.window="selectedImage = null"
                class="space-y-4"
        >
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                @foreach($imageUrls as $imageUrl)
                    <button
                            type="button"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white transition hover:opacity-90 dark:border-gray-700 dark:bg-gray-900"
                            @click="selectedImage = '{{ $imageUrl }}'"
                    >
                        <img
                                src="{{ $imageUrl }}"
                                alt="Artikelbild"
                                class="h-32 w-full object-cover"
                        />
                    </button>
                @endforeach
            </div>

            <template x-if="selectedImage">
                <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/85 p-4"
                     @click.self="selectedImage = null">
                    <button
                            type="button"
                            class="absolute right-4 top-4 rounded-md bg-white/10 px-3 py-2 text-white hover:bg-white/20"
                            @click="selectedImage = null"
                    >
                        Schließen
                    </button>

                    <img
                            :src="selectedImage"
                            alt="Vollansicht"
                            class="max-h-[95vh] max-w-[95vw] object-contain"
                    />
                </div>
            </template>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

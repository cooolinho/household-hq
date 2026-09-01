@php
    use App\Filament\Admin\Resources\Inventory\Collections\CollectionResource;

    $collections = $this->collections;
@endphp

@if($collections->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
        Noch keine Collections vorhanden.
    </div>
@else
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($collections as $collection)
            @php
                $previewImage = $collection->preview_image
                    ? route('admin.inventory.preview', ['type' => 'collection', 'record' => $collection->getKey()])
                    : asset('collection.jpg');

                $viewUrl = CollectionResource::getUrl('view', ['record' => $collection]);
                $editUrl = CollectionResource::getUrl('edit', ['record' => $collection]);
            @endphp

            <article
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <img
                        src="{{ $previewImage }}"
                        alt="{{ $collection->name }}"
                        class="h-44 w-full object-cover"
                />

                <div class="space-y-3 p-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $collection->name }}</h3>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Standorte: {{ $collection->locations_count }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <x-filament::button
                                size="sm"
                                color="gray"
                                tag="a"
                                :href="$viewUrl"
                                icon="heroicon-o-eye"
                        >
                            Details
                        </x-filament::button>

                        <x-filament::button
                                size="sm"
                                color="gray"
                                tag="a"
                                :href="$editUrl"
                                icon="heroicon-o-pencil-square"
                        >
                            Bearbeiten
                        </x-filament::button>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif

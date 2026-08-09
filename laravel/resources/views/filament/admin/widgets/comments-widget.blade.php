<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <span class="flex items-center gap-2">
                <x-heroicon-o-chat-bubble-left-ellipsis class="h-5 w-5 text-primary-500"/>
                Kommentare
            </span>
        </x-slot>

        {{-- Neue Kommentare --}}
        <div class="space-y-2">
            <textarea
                    wire:model="newComment"
                    rows="3"
                    placeholder="Kommentar schreiben …"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder-gray-400
                       focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                       dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500
                       dark:focus:border-primary-500"
            ></textarea>

            @error('newComment')
            <p class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror

            <div class="flex justify-end">
                <x-filament::button
                        wire:click="addComment"
                        wire:loading.attr="disabled"
                        size="sm"
                        icon="heroicon-o-paper-airplane"
                >
                    <span wire:loading.remove wire:target="addComment">Absenden</span>
                    <span wire:loading wire:target="addComment">Wird gespeichert …</span>
                </x-filament::button>
            </div>
        </div>

        {{-- Kommentarliste --}}
        @if($comments->isNotEmpty())
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($comments as $comment)
                    @php
                        /** @var \App\Models\Comment $comment */
                    @endphp
                    <div class="py-3">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40 text-xs font-semibold text-primary-700 dark:text-primary-300 uppercase">
                                    {{ substr($comment->user?->name ?? '?', 0, 1) }}
                                </span>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $comment->user?->name ?? 'Unbekannt' }}
                                </span>
                            </div>
                            <time
                                    datetime="{{ $comment->created_at?->format('Y-m-d\TH:i') }}"
                                    class="text-xs text-gray-400 dark:text-gray-500 shrink-0"
                                    title="{{ $comment->created_at?->format('d.m.Y H:i') }}"
                            >
                                {{ $comment->created_at?->diffForHumans() }}
                            </time>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 p-4">
                            {{ $comment->message }}
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-4 flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-chat-bubble-left class="mb-2 h-8 w-8"/>
                <p class="text-sm italic">Noch keine Kommentare vorhanden.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>


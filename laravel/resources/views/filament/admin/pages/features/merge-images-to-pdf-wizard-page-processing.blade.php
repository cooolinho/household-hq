<div
        class="flex flex-col items-center justify-center py-16 gap-6"
        wire:poll.2000ms="checkJobStatus"
>
    {{-- Spinner --}}
    <div class="relative w-16 h-16">
        <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-gray-200 dark:border-gray-700"></div>
        <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-primary-600 border-t-transparent animate-spin"></div>
    </div>

    {{-- Text --}}
    <div class="text-center space-y-1">
        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">
            PDF wird erstellt&hellip;
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Bitte warten &ndash; der Vorgang läuft im Hintergrund.
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500">
            Diese Seite aktualisiert sich automatisch.
        </p>
    </div>
</div>


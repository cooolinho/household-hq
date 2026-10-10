{{-- Schritt 3: Hinweise zur automatischen Zuordnung und ignorierte Spalten --}}
@php
    $warnings = $this->getMappingWarnings();
    $newColumns = $this->getNewColumns();
    $ignoredColumns = $this->getIgnoredColumns();
    $profile = $this->getSelectedProfile();
@endphp

<div class="space-y-3 text-sm">
    @if ($profile && $warnings === [] && $newColumns === [])
        <div class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300">
            Die Zuordnung aus dem Profil „{{ $profile->name }}“ wurde vollständig übernommen.
        </div>
    @elseif (! $profile)
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
            Ohne Profil werden Spalten anhand typischer Spaltennamen vorgeschlagen. Bitte die Zuordnung prüfen.
        </div>
    @endif

    @if ($warnings !== [])
        <div class="rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">
            <p class="font-medium">Diese Zuordnungen konnten nicht aufgelöst werden:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($warnings as $field => $message)
                    <li><strong>{{ $field }}</strong>: {{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($newColumns !== [])
        <div class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">
            Neue Spalten gegenüber dem Profil: {{ implode(', ', $newColumns) }}
        </div>
    @endif

    @if ($ignoredColumns !== [])
        <p class="text-gray-500 dark:text-gray-400">
            Nicht importiert: {{ implode(', ', $ignoredColumns) }}
        </p>
    @endif
</div>

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">So funktioniert dein Inventar</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Das Inventar ist hierarchisch aufgebaut:
                <strong>Collection</strong> → <strong>Standorte</strong> → <strong>Artikel</strong>.
                Du legst zuerst eine Collection an, darin einen Standort und anschließend einen Artikel.
            </p>

            @if(!$this->hasCollections)
                <div class="mt-4 rounded-lg border border-warning-300/60 bg-warning-50 p-3 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">
                    Du hast aktuell noch keine Collection angelegt. Starte direkt unten mit dem Wizard.
                </div>
            @else
                <div class="mt-4 rounded-lg border border-primary-300/50 bg-primary-50 p-3 text-sm text-primary-800 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-300">
                    Du hast bereits {{ $this->collectionsCount }} Collection(s). Mit dem Wizard kannst du jederzeit eine
                    weitere Struktur anlegen.
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Schnellstart-Wizard</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Der Wizard erstellt in einem Durchlauf genau eine Collection, einen Standort und einen Artikel.
            </p>

            <div class="mt-4">
                {{ $this->content }}
            </div>
        </section>
    </div>
</x-filament-panels::page>

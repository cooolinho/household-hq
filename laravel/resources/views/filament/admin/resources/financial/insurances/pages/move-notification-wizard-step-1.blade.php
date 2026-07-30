<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm">Strasse und Hausnummer (alt)</label>
        <input type="text" wire:model="oldAddress.line1"
               class="mt-1 p-4 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
        @error('oldAddress.line1') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm">Adresszusatz (alt)</label>
        <input type="text" wire:model="oldAddress.line2"
               class="mt-1 p-4 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
        @error('oldAddress.line2') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm">PLZ (alt)</label>
        <input type="text" wire:model="oldAddress.zip"
               class="mt-1 p-4 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
        @error('oldAddress.zip') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm">Stadt (alt)</label>
        <input type="text" wire:model="oldAddress.city"
               class="mt-1 p-4 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
        @error('oldAddress.city') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
        <p class="text-sm font-medium">Neue Adresse aus Profil</p>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $this->newAddress['line1'] ?: '-' }}</p>
        @if(!empty($this->newAddress['line2']))
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $this->newAddress['line2'] }}</p>
        @endif
        <p class="text-sm text-gray-600 dark:text-gray-300">{{ trim($this->newAddress['zip'] . ' ' . $this->newAddress['city']) ?: '-' }}</p>
    </div>
</div>


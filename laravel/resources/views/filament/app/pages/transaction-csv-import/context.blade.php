{{-- Was wird importiert: Datei, Konto, Profil --}}
@php
    $profile = $this->getSelectedProfile();
    $bankAccount = $this->getSelectedBankAccount();
@endphp

<div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-600 dark:text-gray-300">
    <div>
        <span class="text-gray-400 dark:text-gray-500">Datei:</span>
        <span class="font-medium">{{ $this->originalFileName ?? 'noch keine Datei' }}</span>
    </div>
    <div>
        <span class="text-gray-400 dark:text-gray-500">Bankkonto:</span>
        <span class="font-medium">{{ $bankAccount?->name ?? '–' }}</span>
    </div>
    <div>
        <span class="text-gray-400 dark:text-gray-500">Profil:</span>
        <span class="font-medium">{{ $profile?->name ?? 'ohne Profil' }}</span>
    </div>
</div>

@php
    use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
    use App\Models\Financial\Insurance;

    $steps = [
        1 => 'Alte Adresse',
        2 => 'Versicherungen & Kanal',
        3 => 'Vorschau & Versand',
    ];
@endphp

<x-filament-panels::page>
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                @foreach($steps as $number => $label)
                    <span class="inline-flex items-center rounded-full px-3 py-1 {{ $this->step >= $number ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                        {{ $number }}. {{ $label }}
                    </span>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6">
            @if($this->step === 1)
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Schritt 1: Alte Adresse</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Die neue Adresse ist aus deinem Profil
                    uebernommen. Hier bitte nur die alte Adresse eintragen.</p>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm">Strasse und Hausnummer (alt)</label>
                        <input type="text" wire:model="oldAddress.line1"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
                        @error('oldAddress.line1') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm">Adresszusatz (alt)</label>
                        <input type="text" wire:model="oldAddress.line2"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
                    </div>
                    <div>
                        <label class="block text-sm">PLZ (alt)</label>
                        <input type="text" wire:model="oldAddress.zip"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
                        @error('oldAddress.zip') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm">Stadt (alt)</label>
                        <input type="text" wire:model="oldAddress.city"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
                        @error('oldAddress.city') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                    <p class="text-sm font-medium">Neue Adresse aus Profil</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $this->newAddress['line1'] ?: '-' }}</p>
                    @if(!empty($this->newAddress['line2']))
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $this->newAddress['line2'] }}</p>
                    @endif
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ trim($this->newAddress['zip'] . ' ' . $this->newAddress['city']) ?: '-' }}</p>
                </div>
            @endif

            @if($this->step === 2)
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Schritt 2: Versicherungen und
                    Versandart</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Waehle die Versicherungen und den Versandkanal.
                    Fehlende E-Mail-Adressen werden automatisch auf Brief umgestellt.</p>

                @error('selectedInsuranceIds') <p class="mt-3 text-xs text-danger-600">Mindestens eine Versicherung
                    auswaehlen.</p> @enderror

                <div class="mt-4 space-y-3">
                    @foreach($this->insurances as $insurance)
                        @php
                            /** @var Insurance $insurance */
                            $key = (string) $insurance->id;
                        @endphp

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" wire:model="selectedInsuranceIds"
                                           value="{{ $insurance->id }}" class="rounded border-gray-300"/>
                                    <span class="font-medium">{{ $insurance->name }}</span>
                                </label>

                                <div class="w-full sm:w-64">
                                    <select wire:model="channels.{{ $key }}"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                        <option value="{{ InsuranceMoveNotificationChannelEnum::EMAIL->name }}">E-Mail
                                        </option>
                                        <option value="{{ InsuranceMoveNotificationChannelEnum::BRIEF->name }}">Brief
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>E-Mail: {{ $insurance->email ?: '-' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($this->step === 3)
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Schritt 3: Vorschau und
                    Versand</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Texte pruefen, bei Bedarf anpassen und pro
                    Versicherung den Versand bestaetigen.</p>

                <div class="mt-4 space-y-4">
                    @foreach($this->selectedInsuranceIds as $insuranceId)
                        @php
                            $insurance = $this->insurances->firstWhere('id', (int) $insuranceId);
                            if (!$insurance) {
                                continue;
                            }

                            /** @var Insurance $insurance */
                            $key = (string) $insurance->id;
                            $channel = $this->channels[$key] ?? InsuranceMoveNotificationChannelEnum::EMAIL->name;
                            $warnings = $this->warnings[$key] ?? [];
                        @endphp

                        <article class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold">{{ $insurance->name }}</h3>
                                <span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs">
                                    {{ $channel === InsuranceMoveNotificationChannelEnum::EMAIL->name ? 'E-Mail' : 'Brief' }}
                                </span>
                                @if($channel === InsuranceMoveNotificationChannelEnum::EMAIL->name)
                                    <span class="text-xs text-gray-500">({{ $insurance->email ?: '-' }})</span>
                                @endif
                            </div>

                            @foreach($warnings as $warning)
                                <div class="rounded-md bg-warning-50 dark:bg-warning-500/10 p-2 text-xs text-warning-700 dark:text-warning-300">
                                    {{ $warning }}
                                </div>
                            @endforeach

                            <div>
                                <label class="block text-sm">Betreff</label>
                                <input type="text" wire:model="drafts.{{ $key }}.subject"
                                       class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
                            </div>

                            <div>
                                <label class="block text-sm">Text</label>
                                <textarea wire:model="drafts.{{ $key }}.body" rows="12"
                                          class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                            </div>

                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="drafts.{{ $key }}.send"
                                       class="rounded border-gray-300"/>
                                Versenden?
                            </label>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="flex items-center justify-between">
            <x-filament::button color="gray" wire:click="previousStep" :disabled="$this->step === 1"
                                icon="heroicon-o-arrow-left">
                Zurueck
            </x-filament::button>

            @if($this->step < 3)
                <x-filament::button wire:click="nextStep" icon="heroicon-o-arrow-right">
                    Weiter
                </x-filament::button>
            @else
                <x-filament::button wire:click="submit" icon="heroicon-o-paper-airplane">
                    Mitteilungen verarbeiten
                </x-filament::button>
            @endif
        </section>
    </div>
</x-filament-panels::page>


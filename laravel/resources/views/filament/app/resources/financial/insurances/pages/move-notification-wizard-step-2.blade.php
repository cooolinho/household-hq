@php
    use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
    use App\Models\Financial\Insurance;
@endphp

@error('selectedInsuranceIds')
<p class="mb-3 text-xs text-danger-600">Mindestens eine Versicherung auswaehlen.</p>
@enderror

<div class="space-y-3">
    @foreach($this->insurances as $insurance)
        @php
            /** @var Insurance $insurance */
            $key = (string) $insurance->id;
            $currentWarnings = $this->warnings[$key] ?? [];
        @endphp

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model="selectedInsuranceIds" value="{{ $insurance->id }}"
                           class="rounded border-gray-300"/>
                    <span class="font-medium">{{ $insurance->name }}</span>
                </label>

                <div class="w-full sm:w-64">
                    <select wire:model="channels.{{ $key }}"
                            class="w-full p-4 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <option value="{{ InsuranceMoveNotificationChannelEnum::EMAIL->name }}">E-Mail</option>
                        <option value="{{ InsuranceMoveNotificationChannelEnum::BRIEF->name }}">Brief</option>
                    </select>
                </div>
            </div>

            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span>E-Mail: {{ $insurance->email ?: '-' }}</span>
            </div>

            @foreach($currentWarnings as $warning)
                <div class="mt-2 rounded-md bg-warning-50 dark:bg-warning-500/10 p-2 text-xs text-warning-700 dark:text-warning-300">
                    {{ $warning }}
                </div>
            @endforeach
        </div>
    @endforeach
</div>


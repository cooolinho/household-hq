@php
    use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
    use App\Models\Financial\Insurance;
@endphp

<div class="space-y-4">
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
                       class="mt-1 p-4 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"/>
            </div>

            <div>
                <label class="block text-sm">Text</label>
                <textarea wire:model="drafts.{{ $key }}.body" rows="12"
                          class="mt-1 p-4 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="drafts.{{ $key }}.send" class="rounded primary"/>
                Versenden?
            </label>
        </article>
    @endforeach

    @if(empty($this->selectedInsuranceIds))
        <p class="text-sm text-gray-500 dark:text-gray-400">Es wurden keine Versicherungen ausgewaehlt.</p>
    @endif
</div>


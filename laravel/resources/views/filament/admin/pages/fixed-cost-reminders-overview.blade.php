@php
    use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-wide text-gray-500">Gesamt</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->summary['total'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-wide text-gray-500">Aktiv</div>
                <div class="mt-1 text-2xl font-semibold text-success-600 dark:text-success-400">{{ $this->summary['active'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-wide text-gray-500">Heute fällig</div>
                <div class="mt-1 text-2xl font-semibold text-warning-600 dark:text-warning-400">{{ $this->summary['due_today'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-wide text-gray-500">In den nächsten 7 Tagen</div>
                <div class="mt-1 text-2xl font-semibold text-primary-600 dark:text-primary-400">{{ $this->summary['due_next_week'] ?? 0 }}</div>
            </div>
        </section>

        <section
                class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Alle Reminder</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Übersicht über alle konfigurierten Reminder inkl. nächster Erinnerung und Zielkanäle.
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Sichtbare Einträge: {{ $this->summary['visible'] ?? 0 }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 border-b border-gray-200 px-5 py-4 md:grid-cols-3 dark:border-gray-700">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Suche</label>
                    <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Fixkostenname"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-800 p-3"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
                    <select wire:model.live="statusFilter"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-800 p-3">
                        <option value="all">Alle</option>
                        <option value="active">Nur aktiv</option>
                        <option value="inactive">Nur inaktiv</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Kanal</label>
                    <select wire:model.live="channelFilter"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-800 p-3">
                        <option value="all">Alle</option>
                        <option value="mail">Mit E-Mail</option>
                        <option value="notification">Mit Benachrichtigung</option>
                        <option value="both">Nur beides</option>
                    </select>
                </div>
            </div>

            @if(empty($this->reminders))
                <div class="px-5 py-10 text-sm text-gray-500 dark:text-gray-400">
                    Für deine Fixkosten sind noch keine Reminder konfiguriert.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <button type="button" wire:click="sortByColumn('fixed_cost_name')"
                                        class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    Fixkosten <span>{{ $this->sortIndicator('fixed_cost_name') }}</span>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <button type="button" wire:click="sortByColumn('next_booking_timestamp')"
                                        class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    Nächste Buchung <span>{{ $this->sortIndicator('next_booking_timestamp') }}</span>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <button type="button" wire:click="sortByColumn('next_reminder_timestamp')"
                                        class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    Nächste Erinnerung
                                    <span>{{ $this->sortIndicator('next_reminder_timestamp') }}</span>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <button type="button" wire:click="sortByColumn('days_before')"
                                        class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    Vorlauf <span>{{ $this->sortIndicator('days_before') }}</span>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <button type="button" wire:click="sortByColumn('channels_label')"
                                        class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    Kanäle <span>{{ $this->sortIndicator('channels_label') }}</span>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <button type="button" wire:click="sortByColumn('enabled')"
                                        class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    Aktiv <span>{{ $this->sortIndicator('enabled') }}</span>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <button type="button" wire:click="sortByColumn('last_sent_booking_timestamp')"
                                        class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    Zuletzt gesendet
                                    <span>{{ $this->sortIndicator('last_sent_booking_timestamp') }}</span>
                                </button>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @foreach($this->reminders as $reminder)
                            <tr class="align-top">
                                <td class="px-5 py-4">
                                    @if(!empty($reminder['fixed_cost_url']))
                                        <a href="{{ $reminder['fixed_cost_url'] }}"
                                           class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                                            {{ $reminder['fixed_cost_name'] }}
                                        </a>
                                    @else
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $reminder['fixed_cost_name'] }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $reminder['next_booking_date'] }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $reminder['is_due_today'] ? 'bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-300' : ($reminder['is_due_soon'] ? 'bg-primary-100 text-primary-800 dark:bg-primary-500/20 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300') }}">
                                            {{ $reminder['next_reminder_date'] }}
                                        </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $reminder['lead_time_label'] }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $reminder['channels_label'] }}</td>
                                <td class="px-5 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $reminder['enabled'] ? 'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                            {{ $reminder['enabled'] ? 'Ja' : 'Nein' }}
                                        </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $reminder['last_sent_booking_date'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>


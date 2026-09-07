<?php

namespace App\Services\Dashboard\Widgets\Templates;

use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\TextInput;

final class RemindersDashboardWidgetTemplates
{
    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public static function templates(): array
    {
        return [
            new CustomDashboardWidgetTemplate(
                key: 'reminders-overview',
                label: 'Erinnerungen-Übersicht',
                type: CustomDashboardWidgetTypeEnum::STAT,
                configurationKeys: ['days'],
                configurationSchema: fn(): array => [
                    TextInput::make('days')
                        ->label('Zeitraum (Tage)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(90)
                        ->default(7)
                        ->required(),
                ],
                defaultConfiguration: fn(): array => ['days' => 7],
                dataResolver: function (int $userId, array $configuration): array {
                    $days = max(1, min(90, (int)$configuration['days']));
                    $now = CarbonImmutable::now();

                    $reminderScope = fn($query) => $query->whereHas(
                        ReminderSchedule::belongs_to_reminder,
                        fn($reminderQuery) => $reminderQuery
                            ->where(Reminder::user_id, $userId)
                            ->where(Reminder::enabled, true),
                    )->where(ReminderSchedule::enabled, true);

                    $activeReminders = Reminder::query()
                        ->where(Reminder::user_id, $userId)
                        ->where(Reminder::enabled, true)
                        ->count();

                    $upcoming = $reminderScope(ReminderSchedule::query())
                        ->whereBetween(ReminderSchedule::next_due_at, [
                            $now->toDateTimeString(),
                            $now->addDays($days)->toDateTimeString(),
                        ])
                        ->count();

                    $nextDueAt = $reminderScope(ReminderSchedule::query())
                        ->where(ReminderSchedule::next_due_at, '>=', $now->toDateTimeString())
                        ->orderBy(ReminderSchedule::next_due_at)
                        ->value(ReminderSchedule::next_due_at);

                    return [
                        [
                            'label' => 'Aktive Erinnerungen',
                            'value' => (string)$activeReminders,
                            'description' => 'Insgesamt aktiv',
                            'color' => 'primary',
                        ],
                        [
                            'label' => sprintf('Fällig in %d Tagen', $days),
                            'value' => (string)$upcoming,
                            'description' => 'Anzahl anstehender Intervalle',
                            'color' => $upcoming > 0 ? 'warning' : 'success',
                        ],
                        [
                            'label' => 'Nächste Fälligkeit',
                            'value' => $nextDueAt !== null ? CarbonImmutable::parse((string)$nextDueAt)->format('d.m.Y H:i') : '-',
                            'description' => 'Nächster Erinnerungstermin',
                            'color' => 'gray',
                        ],
                    ];
                },
            ),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Mail\ReminderMail;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Models\User;
use App\Services\Reminder\ReminderTargetRegistry;
use Carbon\CarbonImmutable;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Reminder         $reminder,
        public readonly ReminderSchedule $schedule,
        public readonly CarbonImmutable  $dueAt,
    )
    {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->reminder->{Reminder::send_mail} && filled($notifiable->{User::email} ?? null)) {
            $channels[] = 'mail';
        }

        if ($this->reminder->{Reminder::send_notification}) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): Mailable
    {
        [$title, $body, $url] = $this->buildContent();

        return new ReminderMail(
            recipientEmail: (string)($notifiable->{User::email} ?? ''),
            recipientName: (string)($notifiable->{User::name} ?? ''),
            reminderTitle: $title,
            bodyText: $body,
            targetUrl: $url,
            scheduleLabel: $this->schedule->label(),
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string|null} [title, body, url]
     */
    private function buildContent(): array
    {
        $model = $this->reminder->{Reminder::morph_to_remindable};

        if ($model !== null) {
            $registry = app(ReminderTargetRegistry::class);
            $target = $registry->forModel($model::class);
            $dateLabel = $target?->dateLabel((string)$this->reminder->{Reminder::date_property}) ?? (string)$this->reminder->{Reminder::date_property};
            $modelTitle = $target?->resolveTitle($model) ?? $this->reminder->{Reminder::name};
            $url = $target?->resolveUrl($model);

            $title = $this->reminder->{Reminder::name};
            $body = sprintf(
                '%s: %s am %s (%s).',
                $modelTitle,
                $dateLabel,
                $this->dueAt->format('d.m.Y'),
                $this->schedule->label(),
            );

            return [$title, $body, $url];
        }

        $title = $this->reminder->{Reminder::name};
        $body = (string)($this->reminder->{Reminder::message} ?? '');

        return [$title, $body, null];
    }

    /**
     * Dieses Array wird als Filament-Notification in der Tabelle "notifications" abgelegt,
     * damit es in der Topbar-Benachrichtigungsliste korrekt gerendert wird.
     */
    public function toArray(object $notifiable): array
    {
        [$title, $body, $url] = $this->buildContent();

        $notification = FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon(Heroicon::OutlinedBell);

        if ($url !== null) {
            $notification->actions([
                FilamentAction::make('view')
                    ->label('Öffnen')
                    ->url($url),
            ]);
        }

        return $notification->getDatabaseMessage();
    }
}

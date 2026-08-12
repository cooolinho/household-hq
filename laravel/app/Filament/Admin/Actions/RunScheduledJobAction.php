<?php

namespace App\Filament\Admin\Actions;

use App\Services\ScheduledJobRunner;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Throwable;

class RunScheduledJobAction
{
    public const string FIELD_JOB_CLASS = 'job_class';

    public static function make(): Action
    {
        return Action::make('runScheduledJob')
            ->label('Scheduled Job starten')
            ->icon('heroicon-o-play')
            ->color('warning')
            ->modalHeading('Scheduled Job starten')
            ->modalDescription('Wähle einen Job aus app/Jobs/Scheduled und starte ihn manuell.')
            ->schema([
                Select::make(self::FIELD_JOB_CLASS)
                    ->label('Scheduled Job')
                    ->options(fn(ScheduledJobRunner $runner): array => $runner->options())
                    ->required()
                    ->searchable(),
            ])
            ->action(function (array $data, ScheduledJobRunner $runner): void {
                $jobClass = $data[self::FIELD_JOB_CLASS] ?? null;

                if (!is_string($jobClass) || $jobClass === '') {
                    Notification::make()
                        ->title('Bitte einen Scheduled Job auswählen.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $runner->run($jobClass);
                } catch (Throwable $e) {
                    report($e);

                    Notification::make()
                        ->title('Scheduled Job konnte nicht gestartet werden.')
                        ->danger()
                        ->body($e->getMessage())
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Scheduled Job erfolgreich gestartet.')
                    ->success()
                    ->send();
            });
    }
}


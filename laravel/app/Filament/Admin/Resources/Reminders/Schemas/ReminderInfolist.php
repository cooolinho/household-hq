<?php

namespace App\Filament\Admin\Resources\Reminders\Schemas;

use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Services\Reminder\ReminderTargetRegistry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReminderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Allgemein')
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Reminder::name)
                            ->label('Bezeichnung'),
                        TextEntry::make('target')
                            ->label('Bezug')
                            ->state(function (Reminder $record): string {
                                $model = $record->{Reminder::morph_to_remindable};

                                if ($model === null) {
                                    return 'Freier Reminder';
                                }

                                $target = app(ReminderTargetRegistry::class)->forModel($model::class);

                                return sprintf(
                                    '%s: %s (%s)',
                                    $target?->label ?? $model::class,
                                    $target?->resolveTitle($model) ?? '-',
                                    $target?->dateLabel((string)$record->{Reminder::date_property}) ?? (string)$record->{Reminder::date_property},
                                );
                            }),
                        TextEntry::make(Reminder::message)
                            ->label('Text')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('channels')
                            ->label('Kanäle')
                            ->state(fn(Reminder $record): string => Reminder::channelsLabel(
                                (bool)$record->{Reminder::send_mail},
                                (bool)$record->{Reminder::send_notification},
                            )),
                        TextEntry::make(Reminder::enabled)
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(mixed $state): string => $state ? 'Aktiv' : 'Inaktiv')
                            ->color(fn(mixed $state): string => $state ? 'success' : 'gray'),
                    ]),

                Section::make('Intervalle')
                    ->schema([
                        RepeatableEntry::make(Reminder::has_many_schedules)
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('label')
                                    ->label('Intervall')
                                    ->state(fn(ReminderSchedule $record): string => $record->label()),
                                TextEntry::make(ReminderSchedule::next_due_at)
                                    ->label('Nächste Fälligkeit')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make(ReminderSchedule::last_sent_at)
                                    ->label('Zuletzt gesendet')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make(ReminderSchedule::enabled)
                                    ->label('Aktiv')
                                    ->badge()
                                    ->formatStateUsing(fn(mixed $state): string => $state ? 'Ja' : 'Nein')
                                    ->color(fn(mixed $state): string => $state ? 'success' : 'gray'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}

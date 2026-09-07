<?php

namespace App\Filament\Admin\Resources\Reminders\Tables;

use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Services\Reminder\ReminderTargetRegistry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RemindersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(Reminder::name)
            ->columns([
                TextColumn::make(Reminder::name)
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target')
                    ->label('Bezug')
                    ->state(function (Reminder $record): string {
                        $model = $record->{Reminder::morph_to_remindable};

                        if ($model === null) {
                            return 'Freier Reminder';
                        }

                        $target = app(ReminderTargetRegistry::class)->forModel($model::class);

                        return sprintf('%s: %s', $target?->label ?? $model::class, $target?->resolveTitle($model) ?? '-');
                    }),
                TextColumn::make('schedules_count')
                    ->label('Intervalle')
                    ->counts(Reminder::has_many_schedules)
                    ->badge(),
                TextColumn::make('next_due_at')
                    ->label('Nächste Fälligkeit')
                    ->state(fn(Reminder $record): ?string => $record->{Reminder::has_many_schedules}
                        ->pluck(ReminderSchedule::next_due_at)
                        ->filter()
                        ->sort()
                        ->first()
                        ?->format('d.m.Y H:i'))
                    ->placeholder('-'),
                TextColumn::make('channels')
                    ->label('Kanäle')
                    ->state(fn(Reminder $record): string => Reminder::channelsLabel(
                        (bool)$record->{Reminder::send_mail},
                        (bool)$record->{Reminder::send_notification},
                    )),
                ToggleColumn::make(Reminder::enabled)
                    ->label('Aktiv'),
                TextColumn::make(Reminder::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make(Reminder::enabled)
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Aktiv')
                    ->falseLabel('Inaktiv'),
                SelectFilter::make(Reminder::remindable_type)
                    ->label('Ziel')
                    ->options(fn(): array => app(ReminderTargetRegistry::class)->options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(Reminder::created_at, 'desc')
            ->modifyQueryUsing(fn($query) => $query->with(Reminder::has_many_schedules));
    }
}

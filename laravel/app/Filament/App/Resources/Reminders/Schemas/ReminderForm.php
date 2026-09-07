<?php

namespace App\Filament\App\Resources\Reminders\Schemas;

use App\Models\Enums\ReminderOffsetUnitEnum;
use App\Models\Enums\ReminderRecurrenceEnum;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Services\Reminder\ReminderTargetRegistry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ReminderForm
{
    const string FIELD_TARGET_MODE = 'target_mode';
    const string TARGET_MODE_BOUND = 'bound';
    const string TARGET_MODE_FREE = 'free';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::components());
    }

    /**
     * @return array<int, mixed>
     */
    public static function components(): array
    {
        return [
            Section::make('Allgemein')
                ->columns(2)
                ->schema([
                    TextInput::make(Reminder::name)
                        ->label('Bezeichnung')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Radio::make(self::FIELD_TARGET_MODE)
                        ->label('Bezug')
                        ->options([
                            self::TARGET_MODE_BOUND => 'An Datensatz gebunden',
                            self::TARGET_MODE_FREE => 'Freier Reminder',
                        ])
                        ->default(self::TARGET_MODE_BOUND)
                        ->live()
                        ->columnSpanFull()
                        ->dehydrated(true)
                        ->afterStateHydrated(function (Set $set, ?Reminder $record): void {
                            if ($record === null) {
                                return;
                            }

                            $set(
                                self::FIELD_TARGET_MODE,
                                $record->{Reminder::remindable_type} !== null
                                    ? self::TARGET_MODE_BOUND
                                    : self::TARGET_MODE_FREE,
                            );
                        }),

                    Select::make(Reminder::remindable_type)
                        ->label('Model')
                        ->options(fn(): array => app(ReminderTargetRegistry::class)->options())
                        ->required(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                        ->visible(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set(Reminder::date_property, null)),

                    Select::make(Reminder::remindable_id)
                        ->label('Datensatz')
                        ->options(fn(Get $get): array => app(ReminderTargetRegistry::class)->modelOptions($get(Reminder::remindable_type)))
                        ->searchable()
                        ->required(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                        ->visible(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND),

                    Select::make(Reminder::date_property)
                        ->label('Datums-Property')
                        ->options(fn(Get $get): array => app(ReminderTargetRegistry::class)->datePropertyOptions($get(Reminder::remindable_type)))
                        ->required(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                        ->visible(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                        ->columnSpanFull(),

                    Textarea::make(Reminder::message)
                        ->label('Text der Erinnerung')
                        ->rows(3)
                        ->required(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_FREE)
                        ->visible(fn(Get $get): bool => $get(self::FIELD_TARGET_MODE) === self::TARGET_MODE_FREE)
                        ->columnSpanFull(),
                ]),

            Section::make('Intervalle')
                ->description('Ein Reminder kann mehrere Intervalle haben, z. B. 3 Tage und 1 Monat vorher.')
                ->schema([
                    Repeater::make(Reminder::has_many_schedules)
                        ->relationship(Reminder::has_many_schedules)
                        ->hiddenLabel()
                        ->addActionLabel('Intervall hinzufügen')
                        ->defaultItems(1)
                        ->itemLabel(fn(array $state): ?string => (new ReminderSchedule())->forceFill($state)->label())
                        ->schema(static::scheduleComponents())
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Zustellung')
                ->columns(3)
                ->schema([
                    Toggle::make(Reminder::send_mail)
                        ->label('Per E-Mail senden')
                        ->default(true),
                    Toggle::make(Reminder::send_notification)
                        ->label('Per Benachrichtigung senden')
                        ->default(true),
                    Toggle::make(Reminder::enabled)
                        ->label('Aktiv')
                        ->default(true),
                ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function scheduleComponents(): array
    {
        return [
            Select::make(ReminderSchedule::offset_unit)
                ->label('Einheit')
                ->options(ReminderOffsetUnitEnum::options())
                ->required(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                ->visible(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                ->live(),
            TextInput::make(ReminderSchedule::offset_value)
                ->label('Vorlauf')
                ->numeric()
                ->minValue(1)
                ->required(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND)
                ->visible(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_BOUND),

            Select::make(ReminderSchedule::recurrence)
                ->label('Wiederholung')
                ->options(ReminderRecurrenceEnum::options())
                ->required(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_FREE)
                ->visible(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_FREE)
                ->live(),
            TextInput::make(ReminderSchedule::recurrence_value)
                ->label(fn(Get $get): string => ReminderRecurrenceEnum::tryFrom((string)$get(ReminderSchedule::recurrence))?->valueLabel() ?? 'Wert')
                ->numeric()
                ->minValue(1)
                ->visible(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_FREE
                    && (ReminderRecurrenceEnum::tryFrom((string)$get(ReminderSchedule::recurrence))?->requiresValue() ?? false)),
            DatePicker::make(ReminderSchedule::start_date)
                ->label(fn(Get $get): string => ReminderRecurrenceEnum::tryFrom((string)$get(ReminderSchedule::recurrence)) === ReminderRecurrenceEnum::ONCE
                    ? 'Datum'
                    : 'Startdatum')
                ->visible(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_FREE)
                ->required(fn(Get $get): bool => ReminderRecurrenceEnum::tryFrom((string)$get(ReminderSchedule::recurrence)) === ReminderRecurrenceEnum::ONCE),

            TimePicker::make(ReminderSchedule::run_at_time)
                ->label('Uhrzeit')
                ->seconds(false)
                ->helperText('Optional, sonst gilt die globale Standard-Uhrzeit.')
                ->visible(fn(Get $get): bool => $get('../../' . self::FIELD_TARGET_MODE) === self::TARGET_MODE_FREE
                    || $get(ReminderSchedule::offset_unit) !== ReminderOffsetUnitEnum::HOUR->name),

            Toggle::make(ReminderSchedule::enabled)
                ->label('Aktiv')
                ->default(true),
        ];
    }
}

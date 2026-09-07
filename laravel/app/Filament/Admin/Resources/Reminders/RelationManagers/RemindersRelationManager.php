<?php

namespace App\Filament\Admin\Resources\Reminders\RelationManagers;

use App\Models\Enums\ReminderOffsetUnitEnum;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Services\Reminder\ReminderTargetRegistry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Generischer Reminder-RelationManager, der an jedes Model gehängt werden kann, das
 * App\Models\Concerns\HasReminders verwendet (FixedCost, Insurance, Article,
 * MeasurementDeviceContract, ...). Die verfügbaren Datums-Properties werden pro Owner-Model
 * über die ReminderTargetRegistry aufgelöst.
 */
class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'reminders';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(Reminder::name)
                    ->label('Bezeichnung')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make(Reminder::date_property)
                    ->label('Datums-Property')
                    ->options(fn(): array => app(ReminderTargetRegistry::class)->datePropertyOptions($this->getOwnerRecord()::class))
                    ->required()
                    ->columnSpanFull(),
                Repeater::make(Reminder::has_many_schedules)
                    ->relationship(Reminder::has_many_schedules)
                    ->hiddenLabel()
                    ->addActionLabel('Intervall hinzufügen')
                    ->defaultItems(1)
                    ->itemLabel(fn(array $state): ?string => (new ReminderSchedule())->forceFill($state)->label())
                    ->schema([
                        Select::make(ReminderSchedule::offset_unit)
                            ->label('Einheit')
                            ->options(ReminderOffsetUnitEnum::options())
                            ->required()
                            ->live(),
                        TextInput::make(ReminderSchedule::offset_value)
                            ->label('Vorlauf')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TimePicker::make(ReminderSchedule::run_at_time)
                            ->label('Uhrzeit')
                            ->seconds(false)
                            ->helperText('Optional, sonst gilt die globale Standard-Uhrzeit.')
                            ->visible(fn(Get $get): bool => $get(ReminderSchedule::offset_unit) !== ReminderOffsetUnitEnum::HOUR->name),
                        Toggle::make(ReminderSchedule::enabled)
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Toggle::make(Reminder::send_mail)
                    ->label('Per E-Mail senden')
                    ->default(true),
                Toggle::make(Reminder::send_notification)
                    ->label('Per Benachrichtigung senden')
                    ->default(true),
                Toggle::make(Reminder::enabled)
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(Reminder::name)
            ->columns([
                TextColumn::make(Reminder::name)
                    ->label('Bezeichnung'),
                TextColumn::make(Reminder::date_property)
                    ->label('Datums-Property')
                    ->formatStateUsing(fn(mixed $state): string => app(ReminderTargetRegistry::class)
                        ->datePropertyOptions($this->getOwnerRecord()::class)[$state] ?? (string)$state),
                TextColumn::make('schedules_count')
                    ->label('Intervalle')
                    ->counts(Reminder::has_many_schedules)
                    ->badge(),
                IconColumn::make(Reminder::send_mail)
                    ->label('E-Mail')
                    ->boolean(),
                IconColumn::make(Reminder::send_notification)
                    ->label('Benachrichtigung')
                    ->boolean(),
                IconColumn::make(Reminder::enabled)
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data[Reminder::user_id] = auth()->id();

                        return $data;
                    }),
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
            ]);
    }
}

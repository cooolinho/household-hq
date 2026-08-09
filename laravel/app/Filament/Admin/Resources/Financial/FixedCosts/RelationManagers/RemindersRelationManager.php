<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\RelationManagers;

use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostReminder;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = FixedCost::has_many_reminders;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('fixed_cost_next_booking_date_info')
                    ->label('Nächste Buchung')
                    ->columnSpanFull()
                    ->state(fn(): string => Carbon::make($this->ownerRecord?->{FixedCost::next_booking_date})->translatedFormat('d.m.Y')),
                Select::make(FixedCostReminder::days_before)
                    ->label('Vorlauf')
                    ->columnSpanFull()
                    ->options(FixedCostReminder::leadTimeOptions())
                    ->default(1)
                    ->live()
                    ->placeholder('Bitte auswählen')
                    ->helperText(fn(Get $get): string => FixedCostReminder::reminderInfoText($this->ownerRecord?->{FixedCost::next_booking_date}, (int)$get(FixedCostReminder::days_before)))
                    ->required(),
                Toggle::make(FixedCostReminder::send_mail)
                    ->label('Per E-Mail senden')
                    ->default(true),
                Toggle::make(FixedCostReminder::send_notification)
                    ->label('Per Benachrichtigung senden')
                    ->default(false),
                Toggle::make(FixedCostReminder::enabled)
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(FixedCostReminder::days_before)
                    ->label('Vorlauf')
                    ->formatStateUsing(fn(mixed $state): string => FixedCostReminder::leadTimeLabel((int)$state)),
                TextEntry::make('fixed_cost_next_booking_date')
                    ->label('Nächste Buchung')
                    ->state(fn(): mixed => $this->ownerRecord?->{FixedCost::next_booking_date})
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('next_reminder_date')
                    ->label('Nächste Erinnerung')
                    ->state(fn(FixedCostReminder $record) => FixedCostReminder::calculateReminderDate($this->ownerRecord?->{FixedCost::next_booking_date}, (int)$record->{FixedCostReminder::days_before}))
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCostReminder::send_mail)
                    ->label('E-Mail')
                    ->badge()
                    ->formatStateUsing(fn(mixed $state): string => $state ? 'Ja' : 'Nein')
                    ->color(fn(mixed $state): string => $state ? 'success' : 'gray'),
                TextEntry::make(FixedCostReminder::send_notification)
                    ->label('Benachrichtigung')
                    ->badge()
                    ->formatStateUsing(fn(mixed $state): string => $state ? 'Ja' : 'Nein')
                    ->color(fn(mixed $state): string => $state ? 'success' : 'gray'),
                TextEntry::make(FixedCostReminder::enabled)
                    ->label('Aktiv')
                    ->badge()
                    ->formatStateUsing(fn(mixed $state): string => $state ? 'Ja' : 'Nein')
                    ->color(fn(mixed $state): string => $state ? 'success' : 'gray'),
                TextEntry::make(FixedCostReminder::last_sent_booking_date)
                    ->label('Zuletzt gesendet für Buchung am')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCostReminder::created_at)
                    ->label('Erstellt am')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(FixedCostReminder::updated_at)
                    ->label('Zuletzt aktualisiert am')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(FixedCostReminder::days_before)
            ->columns([
                TextColumn::make(FixedCostReminder::days_before)
                    ->label('Vorlauf')
                    ->formatStateUsing(fn(mixed $state): string => FixedCostReminder::leadTimeLabel((int)$state)),
                TextColumn::make('next_reminder_date')
                    ->label('Nächste Erinnerung')
                    ->state(fn(FixedCostReminder $record) => FixedCostReminder::calculateReminderDate($this->ownerRecord?->{FixedCost::next_booking_date}, (int)$record->{FixedCostReminder::days_before}))
                    ->date()
                    ->placeholder('-'),
                IconColumn::make(FixedCostReminder::send_mail)
                    ->label('E-Mail')
                    ->boolean(),
                IconColumn::make(FixedCostReminder::send_notification)
                    ->label('Benachrichtigung')
                    ->boolean(),
                IconColumn::make(FixedCostReminder::enabled)
                    ->label('Aktiv')
                    ->boolean(),
                TextColumn::make(FixedCostReminder::last_sent_booking_date)
                    ->label('Zuletzt gesendet für')
                    ->date()
                    ->placeholder('-'),
                TextColumn::make(FixedCostReminder::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(FixedCostReminder::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
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


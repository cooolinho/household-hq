<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\RelationManagers;

use App\Models\ContactPerson;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Enums\ContactPersonTypeEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = MeasurementDeviceContract::belongs_to_many_contacts;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(ContactPerson::lastname)
            ->columns([
                TextColumn::make(ContactPerson::firstname)
                    ->label('Vorname')
                    ->searchable(),
                TextColumn::make(ContactPerson::lastname)
                    ->label('Nachname')
                    ->searchable(),
                TextColumn::make(ContactPerson::type)
                    ->label('Kontaktart')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => ContactPersonTypeEnum::tryFromName($state)?->label() ?? (string)$state),
                TextColumn::make(ContactPerson::role)
                    ->label('Rolle')
                    ->placeholder('-'),
                TextColumn::make(ContactPerson::email)
                    ->label('E-Mail')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make(ContactPerson::phone_business)
                    ->label('Telefon')
                    ->placeholder('-'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Ansprechpartner zuweisen')
                    ->recordSelectOptionsQuery(fn(Builder $query) => $query
                        ->where(ContactPerson::user_id, auth()->id()))
                    ->recordSelectSearchColumns([
                        ContactPerson::firstname,
                        ContactPerson::lastname,
                        ContactPerson::email,
                        ContactPerson::role,
                    ]),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Entfernen'),
            ]);
    }
}

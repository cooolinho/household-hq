<?php

namespace App\Filament\App\Resources\ContactPeople\Tables;

use App\Models\ContactPerson;
use App\Models\Enums\ContactPersonTypeEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactPeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(ContactPerson::title)
                    ->searchable(),
                TextColumn::make(ContactPerson::firstname)
                    ->searchable(),
                TextColumn::make(ContactPerson::lastname)
                    ->searchable(),
                TextColumn::make(ContactPerson::phone_private)
                    ->searchable(),
                TextColumn::make(ContactPerson::phone_business)
                    ->searchable(),
                TextColumn::make(ContactPerson::email)
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make(ContactPerson::avatar)
                    ->searchable(),
                TextColumn::make(ContactPerson::role)
                    ->searchable(),
                TextColumn::make(ContactPerson::type)
                    ->label('Kontaktart')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => ContactPersonTypeEnum::tryFromName($state)?->label() ?? (string)$state),
                TextColumn::make(ContactPerson::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(ContactPerson::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

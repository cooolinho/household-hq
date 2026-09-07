<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\Actions\ResendVerificationEmailAction;
use App\Filament\Admin\Resources\Users\Actions\ToggleUserActiveAction;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(User::name)
            ->columns([
                TextColumn::make(User::name)
                    ->label(__('admin.resource.user.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make(User::email)
                    ->label(__('admin.resource.user.fields.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make(User::role)
                    ->label(__('admin.resource.user.fields.role'))
                    ->badge()
                    ->sortable(),
                IconColumn::make(User::email_verified_at)
                    ->label(__('admin.resource.user.fields.email_verified_at'))
                    ->boolean()
                    ->state(fn (User $record): bool => $record->hasVerifiedEmail()),
                IconColumn::make(User::is_active)
                    ->label(__('admin.resource.user.fields.is_active'))
                    ->boolean(),
                TextColumn::make(User::created_at)
                    ->label(__('admin.resource.user.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make(User::role)
                    ->label(__('admin.resource.user.fields.role'))
                    ->options(Role::class),
                TernaryFilter::make(User::is_active)
                    ->label(__('admin.resource.user.fields.is_active')),
                TernaryFilter::make('verified')
                    ->label(__('admin.resource.user.fields.email_verified_at'))
                    ->queries(
                        fn (Builder $query): Builder => $query->whereNotNull(User::email_verified_at),
                        fn (Builder $query): Builder => $query->whereNull(User::email_verified_at),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ResendVerificationEmailAction::make(),
                ToggleUserActiveAction::make(),
                // Row-Action-Sichtbarkeit wird nicht automatisch aus
                // UserResource::canDelete() abgeleitet - Filament wendet
                // Resource::can*() nur beim direkten Page-Aufruf (view/edit)
                // an, nicht auf Tabellen-Actions. Deshalb hier explizit.
                DeleteAction::make()
                    ->visible(fn (User $record): bool => UserResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn (User $record): bool => UserResource::canDelete($record)),
                ]),
            ]);
    }
}

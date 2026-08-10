<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Tables;

use App\Models\Enums\InsuranceMoveNotificationStatusEnum;
use App\Models\Financial\Insurance;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InsurancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Insurance::name)
                    ->searchable(),
                TextColumn::make(Insurance::belongs_to_category . '.name')
                    ->label('Kategorie')
                    ->badge()
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make(Insurance::move_notification_status)
                    ->label('Umzug-Status')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) {
                            return '-';
                        }

                        foreach (InsuranceMoveNotificationStatusEnum::cases() as $case) {
                            if ($case->name === $state) {
                                return $case->label();
                            }
                        }

                        return $state;
                    })
                    ->color(function (?string $state): string {
                        if (!$state) {
                            return 'gray';
                        }

                        foreach (InsuranceMoveNotificationStatusEnum::cases() as $case) {
                            if ($case->name === $state) {
                                return $case->color();
                            }
                        }

                        return 'gray';
                    }),
                TextColumn::make(Insurance::move_notified_at)
                    ->label('Zuletzt mitgeteilt')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make(Insurance::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Insurance::updated_at)
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

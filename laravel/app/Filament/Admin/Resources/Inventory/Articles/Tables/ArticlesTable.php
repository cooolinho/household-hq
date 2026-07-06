<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Tables;

use App\Models\Inventory\Article;
use App\Models\Inventory\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Article::name)
                    ->searchable(),
                TextColumn::make(Article::amount)
                    ->numeric()
                    ->sortable(),
                TextColumn::make(Article::serial_number)
                    ->searchable(),
                TextColumn::make(Article::model_number)
                    ->searchable(),
                TextColumn::make(Article::manufacturer)
                    ->searchable(),
                IconColumn::make(Article::insured)
                    ->boolean(),
                IconColumn::make(Article::archived)
                    ->boolean(),
                TextColumn::make(Article::parent_id)
                    ->numeric()
                    ->sortable(),
                TextColumn::make(Article::belongs_to_location . '.' . Location::name)
                    ->searchable(),
                TextColumn::make(Article::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Article::updated_at)
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

<?php

namespace App\Filament\App\Resources\Inventory\Articles\Tables;

use App\Filament\App\Resources\Inventory\Articles\ArticleResource;
use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview_image')
                    ->label('Vorschau')
                    ->getStateUsing(function (Article $record): ?string {
                        $previewImage = $record->previewImages->first();

                        return $previewImage
                            ? route('app.inventory.preview', ['type' => 'article-image', 'record' => $previewImage->getKey()])
                            : null;
                    })
                    ->defaultImageUrl(asset('article.jpg')),
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
                SelectFilter::make('collection_id')
                    ->label('Collection')
                    ->options(fn() => Collection::query()
                        ->where(Collection::user_id, auth()->id())
                        ->orderBy(Collection::name)
                        ->pluck(Collection::name, Collection::id)
                        ->toArray())
                    ->query(fn(Builder $query, array $data): Builder => isset($data['value']) && filled($data['value'])
                        ? $query->whereHas(
                            Article::belongs_to_location,
                            fn(Builder $locationQuery) => $locationQuery->where(Location::collection_id, (int)$data['value'])
                        )
                        : $query),
                SelectFilter::make(Article::location_id)
                    ->label('Standort')
                    ->options(fn() => Location::query()
                        ->whereHas(
                            Location::belongs_to_collection,
                            fn(Builder $query) => $query->where(Collection::user_id, auth()->id())
                        )
                        ->orderBy(Location::name)
                        ->pluck(Location::name, Location::id)
                        ->toArray())
                    ->query(fn(Builder $query, array $data): Builder => isset($data['value']) && filled($data['value'])
                        ? $query->where(Article::location_id, (int)$data['value'])
                        : $query),
                SelectFilter::make(Article::insured)
                    ->label('Versichert')
                    ->options([
                        '1' => 'Ja',
                        '0' => 'Nein',
                    ])
                    ->query(fn(Builder $query, array $data): Builder => isset($data['value']) && $data['value'] !== ''
                        ? $query->where(Article::insured, $data['value'] === '1')
                        : $query),
                SelectFilter::make(Article::archived)
                    ->label('Archiviert')
                    ->options([
                        '1' => 'Ja',
                        '0' => 'Nein',
                    ])
                    ->query(fn(Builder $query, array $data): Builder => isset($data['value']) && $data['value'] !== ''
                        ? $query->where(Article::archived, $data['value'] === '1')
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Details')
                    ->modalHeading('Artikeldetails')
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->extraModalFooterActions([
                        Action::make('open_full_view')
                            ->label('Vollansicht öffnen')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->url(fn(Article $record): string => ArticleResource::getUrl('view', ['record' => $record])),
                    ]),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

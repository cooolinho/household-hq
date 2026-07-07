<?php

namespace App\Filament\Admin\Resources\Inventory\Articles;

use App\Filament\Admin\Resources\Inventory\Articles\Pages\CreateArticle;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\EditArticle;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\ListArticles;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\ViewArticle;
use App\Filament\Admin\Resources\Inventory\Articles\RelationManagers\DocumentsRelationManager;
use App\Filament\Admin\Resources\Inventory\Articles\Schemas\ArticleForm;
use App\Filament\Admin\Resources\Inventory\Articles\Schemas\ArticleInfolist;
use App\Filament\Admin\Resources\Inventory\Articles\Tables\ArticlesTable;
use App\Models\Inventory\Article;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|null|\UnitEnum $navigationGroup = 'Inventar';
    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = Article::name;

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ArticleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'view' => ViewArticle::route('/{record}'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Article) {
            return $record->location?->collection?->user_id === auth()->id();
        }

        return false;
    }
}

<?php

namespace App\Filament\App\Resources\Inventory\Articles;

use App\Filament\App\Resources\Inventory\Articles\Pages\CreateArticle;
use App\Filament\App\Resources\Inventory\Articles\Pages\EditArticle;
use App\Filament\App\Resources\Inventory\Articles\Pages\ListArticles;
use App\Filament\App\Resources\Inventory\Articles\Pages\ViewArticle;
use App\Filament\App\Resources\Inventory\Articles\RelationManagers\DocumentsRelationManager;
use App\Filament\App\Resources\Inventory\Articles\Schemas\ArticleForm;
use App\Filament\App\Resources\Inventory\Articles\Schemas\ArticleInfolist;
use App\Filament\App\Resources\Inventory\Articles\Tables\ArticlesTable;
use App\Filament\App\Resources\Inventory\Support\InventoryNavigationVisibility;
use App\Filament\App\Resources\Reminders\RelationManagers\RemindersRelationManager;
use App\Menu\NavigationGroup;
use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::INVENTORY;
    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = Article::name;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.article.navigation_label');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return InventoryNavigationVisibility::hasArticlesForCurrentUser();
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.article.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.article.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereHas(
            Article::belongs_to_location . '.' . Location::belongs_to_collection,
            fn($q) => $q->where(Collection::user_id, auth()->id())
        )->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

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
        return ArticlesTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->with(Article::has_many_preview_images)->whereHas(
                    Article::belongs_to_location . '.' . Location::belongs_to_collection,
                    fn($collectionQuery) => $collectionQuery->where(Collection::user_id, auth()->id())
                );
            });
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            RemindersRelationManager::class,
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

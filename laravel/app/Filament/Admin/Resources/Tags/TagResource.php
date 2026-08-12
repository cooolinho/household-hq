<?php
namespace App\Filament\Admin\Resources\Tags;
use App\Filament\Admin\Resources\Tags\Pages\CreateTag;
use App\Filament\Admin\Resources\Tags\Pages\EditTag;
use App\Filament\Admin\Resources\Tags\Pages\ListTags;
use App\Filament\Admin\Resources\Tags\Pages\ViewTag;
use App\Filament\Admin\Resources\Tags\Schemas\TagForm;
use App\Filament\Admin\Resources\Tags\Schemas\TagInfolist;
use App\Filament\Admin\Resources\Tags\Tables\TagsTable;
use App\Models\Tag;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static ?string $recordTitleAttribute = Tag::name;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.tag.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.tag.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.tag.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(Tag::user_id, auth()->id())->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }
    public static function infolist(Schema $schema): Schema
    {
        return TagInfolist::configure($schema);
    }
    public static function table(Table $table): Table
    {
        return TagsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(Tag::user_id, auth()->id());
            });
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'view' => ViewTag::route('/{record}'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }
    public static function canView(Model $record): bool
    {
        if ($record instanceof Tag) {
            return $record->user_id === auth()->id();
        }
        return false;
    }

    public static function getMorphToManySelect(Schema $schema, string $relationship, string $property = 'tags')
    {
        return Select::make($property)
            ->relationship($relationship, Tag::name)
            ->multiple()
            ->preload()
            ->searchable()
            ->createOptionForm(TagForm::configure($schema)->getComponents())
            ->placeholder('Select tags');
    }
}

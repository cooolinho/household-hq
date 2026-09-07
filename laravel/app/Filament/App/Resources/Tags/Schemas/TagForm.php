<?php

namespace App\Filament\App\Resources\Tags\Schemas;

use App\Models\Tag;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        if ($schema->getRecord() instanceof Tag) {
            return $schema->schema(TagForm::getEditForm());
        }

        return $schema->components(self::getCreateForm());
    }

    /**
     * @return array
     */
    public static function getEditForm(): array
    {
        return [
            KeyValue::make(Tag::name)
                ->keyLabel('Locale')
                ->valueLabel('Übersetzung'),
            TextInput::make(Tag::type),
            TextInput::make(Tag::order_column)
                ->numeric(),
        ];
    }

    /**
     * @return array
     */
    private static function getCreateForm(): array
    {
        return [
            Hidden::make(Tag::user_id)
                ->default(fn() => auth()->id()),
            TextInput::make(Tag::name)
                ->required(),
            TextInput::make(Tag::type),
            TextInput::make(Tag::order_column)
                ->numeric(),
        ];
    }
}

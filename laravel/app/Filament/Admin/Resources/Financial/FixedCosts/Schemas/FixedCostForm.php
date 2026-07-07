<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Schemas;

use App\Models\Enums\FixedCostCategoryEnum;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FixedCostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getSchema());
    }

    public static function getSchema(): array
    {
        return [
            Section::make('base')
                ->heading(false)
                ->columnSpanFull()
                ->schema(self::getSectionBaseSchema()),

            Section::make('interval')
                ->heading(false)
                ->columnSpanFull()
                ->schema(self::getSectionIntervalSchema()),

            Section::make('ends_selection')
                ->heading(false)
                ->columnSpanFull()
                ->schema(self::getSectionEndingSchema()),

//            TagResource::getMorphToManySelect($schema, FixedCost::morph_to_many_tags)
        ];
    }

    /**
     * @return array
     */
    private static function getSectionBaseSchema(): array
    {
        return [
            TextInput::make(FixedCost::name)
                ->required(),
            TextInput::make(FixedCost::amount)
                ->required()
                ->prefixIconColor(function (Get $get) {
                    $amount = $get(FixedCost::amount);
                    if ($amount !== null && $amount < 0) {
                        return 'danger';
                    }
                    return 'success';
                })
                ->postfix('EUR')
                ->prefixAction(Action::make('switch_positive_negative')
                    ->icon(function (Get $get) {
                        $amount = $get(FixedCost::amount);
                        if ($amount !== null && $amount < 0) {
                            return Heroicon::MinusCircle;
                        }
                        return Heroicon::PlusCircle;
                    })
                    ->color(function (Get $get) {
                        $amount = $get(FixedCost::amount);
                        if ($amount !== null && $amount < 0) {
                            return 'danger';
                        }
                        return 'success';
                    })
                    ->action(function (Get $get, Set $set) {
                        $amount = $get(FixedCost::amount);
                        if ($amount !== null) {
                            $set(FixedCost::amount, -1 * $amount);
                        }
                    }))
                ->numeric(),
            Select::make(FixedCost::category)
                ->options(FixedCostCategoryEnum::options())
                ->default(FixedCostCategoryEnum::default())
                ->searchable()
                ->preload()
                ->required(),
        ];
    }

    /**
     * @return array
     */
    private static function getSectionIntervalSchema(): array
    {
        return [
            Select::make(FixedCost::interval)
                ->options(FixedCostIntervalEnum::options())
                ->default(FixedCostIntervalEnum::default())
                ->required(),
        ];
    }

    /**
     * @return array
     */
    private static function getSectionEndingSchema(): array
    {
        return [
            Select::make(FixedCost::ends_mode)
                ->options(FixedCostEndsModeEnum::options())
                ->default(FixedCostEndsModeEnum::default())
                ->reactive()
                ->required(),
            DatePicker::make(FixedCost::ends_date)
                ->visible(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::ENDS->name)
                ->required(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::ENDS->name),
            Select::make(FixedCost::ends_interval)
                ->visible(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::ENDS->name)
                ->options(FixedCostIntervalEnum::options())
                ->default(FixedCostIntervalEnum::default())
                ->required(),

            DatePicker::make(FixedCost::extended_date)
                ->visible(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::EXTENDED->name)
                ->required(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::EXTENDED->name),
            Select::make(FixedCost::extended_interval)
                ->visible(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::EXTENDED->name)
                ->options(FixedCostIntervalEnum::options())
                ->default(FixedCostIntervalEnum::default())
                ->required(),
        ];
    }
}

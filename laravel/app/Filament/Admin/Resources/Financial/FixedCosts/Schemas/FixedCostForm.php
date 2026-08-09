<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Schemas;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostCategory;
use App\Models\Financial\Insurance;
use App\Rules\FixedCostAmountNotZeroRule;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FixedCostForm
{
    private const string NO_CATEGORY_OPTION = '__none__';

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
                ->columns(2)
                ->schema(self::getSectionBaseSchema()),

            Section::make('interval')
                ->heading(false)
                ->columnSpanFull()
                ->columns(2)
                ->schema(self::getSectionIntervalSchema()),

            Section::make('ends_selection')
                ->heading(false)
                ->columnSpanFull()
                ->columns(2)
                ->schema(self::getSectionEndingSchema()),

            Section::make('notes')
                ->heading(false)
                ->columnSpanFull()
                ->schema(self::getSectionNotesSchema()),
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
                ->numeric()
                ->rule(new FixedCostAmountNotZeroRule()),
            Select::make(FixedCost::category_id)
                ->columnSpanFull()
                ->label('Kategorie')
                ->options(fn() => self::groupedCategoryOptions())
                ->default(self::NO_CATEGORY_OPTION)
                ->formatStateUsing(fn($state) => $state ?? self::NO_CATEGORY_OPTION)
                ->dehydrateStateUsing(fn($state) => $state === self::NO_CATEGORY_OPTION ? null : (int)$state)
                ->searchable()
                ->preload()
                ->reactive(),

            Checkbox::make('assign_with_insurance')
                ->label('Mit Versicherung verknüpfen?')
                ->reactive(),

            Select::make(FixedCost::insurance_id)
                ->label('Versicherung')
                ->options(Insurance::all()->pluck('name', 'id')->toArray())
                ->searchable()
                ->visible(function (Get $get) {
                    return $get('assign_with_insurance') === true;
                })
                ->preload(),
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

            DatePicker::make(FixedCost::next_booking_date)
                ->label('Nächste Buchung am')
                ->default(Carbon::now()->addMonth()->startOfMonth())
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
                ->columnSpanFull()
                ->options(FixedCostEndsModeEnum::options())
                ->default(FixedCostEndsModeEnum::default())
                ->reactive()
                ->required(),
            DatePicker::make(FixedCost::ends_date)
                ->visible(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::ENDS->name)
                ->required(fn(Get $get) => $get(FixedCost::ends_mode) === FixedCostEndsModeEnum::ENDS->name),

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

    private static function getSectionNotesSchema(): array
    {
        return [
            Textarea::make(FixedCost::notes)
                ->rows(5),
        ];
    }

    private static function groupedCategoryOptions(): array
    {
        $grouped = [
            'Nicht kategorisiert' => [
                self::NO_CATEGORY_OPTION => 'Keine Kategorie',
            ],
        ];

        $categories = FixedCostCategory::query()
            ->orderBy(FixedCostCategory::group)
            ->orderBy(FixedCostCategory::name)
            ->get();

        foreach ($categories as $category) {
            $group = $category->{FixedCostCategory::group} ?: 'Nicht kategorisiert';
            $grouped[$group][(string)$category->id] = $category->{FixedCostCategory::name};
        }

        return $grouped;
    }
}

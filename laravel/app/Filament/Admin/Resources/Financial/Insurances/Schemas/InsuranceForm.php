<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Schemas;

use App\Filament\Admin\Resources\Financial\InsuranceCategories\Schemas\InsuranceCategoryForm;
use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\Financial\Insurance;
use App\Models\Financial\InsuranceCategory;
use App\Util\CountriesUtil;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InsuranceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Basisinformationen')
                    ->columnSpanFull()
                    ->heading('Basisinformationen')
                    ->description('Hier können Sie die Basisinformationen der Versicherung bearbeiten.')
                    ->schema([
                        TextInput::make(Insurance::name)
                            ->required(),
                        TextInput::make(Insurance::company),
                        self::getBelongsToCategorySelect($schema),
                        TextInput::make(Insurance::number),
                        DatePicker::make(Insurance::start_date),
                        DatePicker::make(Insurance::end_date),
                    ]),


                Section::make('Kontaktinformationen')
                    ->columnSpanFull()
                    ->heading('Kontaktinformationen')
                    ->description('Hier können Sie die Kontaktinformationen der Versicherung bearbeiten.')
                    ->schema([
                        TextInput::make(Insurance::contact_person),
                        TextInput::make(Insurance::phone),
                        TextInput::make(Insurance::email),
                    ]),


                // address columns
                Section::make('Adresse')
                    ->columnSpanFull()
                    ->heading('Adresse')
                    ->description('Hier können Sie die Adresse der Versicherung bearbeiten.')
                    ->columns(2)
                    ->schema([
                        TextInput::make(Insurance::address_line_1)
                            ->columnSpan(2),
                        TextInput::make(Insurance::address_line_2)
                            ->columnSpan(2),
                        TextInput::make(Insurance::address_zip)
                            ->columnSpan(1),
                        TextInput::make(Insurance::address_city)
                            ->columnSpan(1),
                        Select::make(Insurance::address_country)
                            ->options(CountriesUtil::options())
                    ]),

                TagResource::getMorphToManySelect($schema, Insurance::morph_to_many_tags),
            ]);
    }

    public static function getBelongsToCategorySelect(
        Schema $schema,
        string $inputName = Insurance::category_id
    ): Select
    {
        return Select::make($inputName)
            ->label(__('admin.resource.insurance_category.model_label'))
            ->options(function () {
                $grouped = [];

                $categories = InsuranceCategory::query()
                    ->orderBy(InsuranceCategory::group)
                    ->orderBy(InsuranceCategory::name)
                    ->get();

                /** @var InsuranceCategory $category */
                foreach ($categories as $category) {
                    $grouped[$category->{InsuranceCategory::group}][(string)$category->id] = $category->{InsuranceCategory::name};
                }

                // group "GROUP_NOT_CATEGORIZED" at the end
                if (isset($grouped[InsuranceCategory::GROUP_NOT_CATEGORIZED])) {
                    $notCategorized = $grouped[InsuranceCategory::GROUP_NOT_CATEGORIZED];
                    unset($grouped[InsuranceCategory::GROUP_NOT_CATEGORIZED]);
                    $grouped[InsuranceCategory::GROUP_NOT_CATEGORIZED] = $notCategorized;
                }

                return $grouped;
            })
            ->preload()
            ->searchable()
            ->createOptionForm(InsuranceCategoryForm::configure($schema)->getComponents())
            ->createOptionUsing(function (array $data) {
                return InsuranceCategory::query()->create($data)->id;
            })
            ->placeholder('Kategorie auswählen');
    }
}

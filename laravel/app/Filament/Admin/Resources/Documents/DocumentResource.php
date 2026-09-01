<?php

namespace App\Filament\Admin\Resources\Documents;

use App\Filament\Admin\Resources\Documents\Pages\CreateArticleDocument;
use App\Filament\Admin\Resources\Documents\Pages\CreateDocument;
use App\Filament\Admin\Resources\Documents\Pages\CreateFixedCostDocument;
use App\Filament\Admin\Resources\Documents\Pages\CreateInsuranceDocument;
use App\Filament\Admin\Resources\Documents\Pages\CreateMeasurementDeviceContractDocument;
use App\Filament\Admin\Resources\Documents\Pages\EditDocument;
use App\Filament\Admin\Resources\Documents\Pages\ListDocuments;
use App\Filament\Admin\Resources\Documents\Pages\ViewDocument;
use App\Filament\Admin\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Admin\Resources\Documents\Schemas\DocumentInfolist;
use App\Filament\Admin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentResource extends Resource
{
    const string PAGE_CREATE_FOR_INSURANCE = 'create-insurance';
    const string PAGE_CREATE_FOR_FIXED_COST = 'create-fixed-cost';
    const string PAGE_CREATE_FOR_ARTICLE = 'create-article';
    const string PAGE_CREATE_FOR_MEASUREMENT_DEVICE_CONTRACT = 'create-measurement-device-contract';

    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = Document::path;

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query->where(Document::user_id, auth()->id());
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
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            self::PAGE_CREATE_FOR_INSURANCE => CreateInsuranceDocument::route('/insurances/{owner}/create'),
            self::PAGE_CREATE_FOR_FIXED_COST => CreateFixedCostDocument::route('/fixed-costs/{owner}/create'),
            self::PAGE_CREATE_FOR_ARTICLE => CreateArticleDocument::route('/articles/{owner}/create'),
            self::PAGE_CREATE_FOR_MEASUREMENT_DEVICE_CONTRACT => CreateMeasurementDeviceContractDocument::route('/measurement-device-contracts/{owner}/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Document) {
            return $record->user_id === auth()->id();
        }

        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.document.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.document.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.document.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(Document::user_id, auth()->id())->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}

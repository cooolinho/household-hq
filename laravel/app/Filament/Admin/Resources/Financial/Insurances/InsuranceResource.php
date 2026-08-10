<?php

namespace App\Filament\Admin\Resources\Financial\Insurances;

use App\Filament\Admin\Resources\Financial\Insurances\Pages\CreateInsurance;
use App\Filament\Admin\Resources\Financial\Insurances\Pages\EditInsurance;
use App\Filament\Admin\Resources\Financial\Insurances\Pages\ListInsurances;
use App\Filament\Admin\Resources\Financial\Insurances\Pages\ViewInsurance;
use App\Filament\Admin\Resources\Financial\Insurances\RelationManagers\DocumentsRelationManager;
use App\Filament\Admin\Resources\Financial\Insurances\Schemas\InsuranceForm;
use App\Filament\Admin\Resources\Financial\Insurances\Schemas\InsuranceInfolist;
use App\Filament\Admin\Resources\Financial\Insurances\Tables\InsurancesTable;
use App\Menu\NavigationGroup;
use App\Models\Financial\Insurance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InsuranceResource extends Resource
{
    const string PAGE_VIEW = 'view';
    const string PAGE_EDIT = 'edit';
    const string PAGE_INDEX = 'index';


    protected static ?string $model = Insurance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::INSURANCES;
    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = Insurance::name;

    public static function form(Schema $schema): Schema
    {
        return InsuranceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InsuranceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsurancesTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $query
                    ->where(Insurance::user_id, auth()->id())
                    ->with(Insurance::belongs_to_category);
            });
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
            'index' => ListInsurances::route('/'),
//            'wizard-move-notification' => MoveNotificationWizard::route('/wizard-move-notification'),
//            'move-notification-templates' => MoveNotificationTemplates::route('/move-notification-templates'),
            'create' => CreateInsurance::route('/create'),
            self::PAGE_VIEW => ViewInsurance::route('/{record}'),
            'edit' => EditInsurance::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Insurance) {
            return $record->user_id === auth()->id();
        }

        return false;
    }

    public static function getViewUrl(int $recordId): string
    {
        return self::getUrl(self::PAGE_VIEW, ['record' => $recordId]);
    }

    public static function getEditUrl(int $recordId): string
    {
        return self::getUrl(self::PAGE_EDIT, ['record' => $recordId]);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.insurance.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.insurance.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.insurance.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(Insurance::user_id, auth()->id())->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}

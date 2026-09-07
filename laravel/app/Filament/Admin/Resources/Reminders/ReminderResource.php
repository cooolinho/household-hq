<?php

namespace App\Filament\Admin\Resources\Reminders;

use App\Filament\Admin\Resources\Reminders\Pages\CreateReminder;
use App\Filament\Admin\Resources\Reminders\Pages\EditReminder;
use App\Filament\Admin\Resources\Reminders\Pages\ListReminders;
use App\Filament\Admin\Resources\Reminders\Pages\ViewReminder;
use App\Filament\Admin\Resources\Reminders\Schemas\ReminderForm;
use App\Filament\Admin\Resources\Reminders\Schemas\ReminderInfolist;
use App\Filament\Admin\Resources\Reminders\Tables\RemindersTable;
use App\Jobs\Scheduled\SendRemindersJob;
use App\Menu\NavigationGroup;
use App\Models\Reminder;
use App\Settings\ReminderSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReminderResource extends Resource
{
    const string PAGE_VIEW = 'view';
    const string PAGE_EDIT = 'edit';

    protected static ?string $model = Reminder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::REMINDERS;
    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = Reminder::name;

    public static function form(Schema $schema): Schema
    {
        return ReminderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReminderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RemindersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(Reminder::user_id, auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReminders::route('/'),
            'create' => CreateReminder::route('/create'),
            self::PAGE_VIEW => ViewReminder::route('/{record}'),
            self::PAGE_EDIT => EditReminder::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::canView($record);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Reminder && $record->{Reminder::user_id} === auth()->id();
    }

    public static function canDelete(Model $record): bool
    {
        return self::canView($record);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.reminder.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.reminder.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.reminder.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where(Reminder::user_id, auth()->id())->count();

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function getSendRemindersNowAction(): Action
    {
        return Action::make('send_reminders_now')
            ->label('Erinnerungen jetzt prüfen')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('warning')
            ->requiresConfirmation()
            ->action(function (): void {
                if (!app(ReminderSettings::class)->enabled) {
                    Notification::make()
                        ->warning()
                        ->title('Erinnerungen sind global deaktiviert.')
                        ->send();

                    return;
                }

                SendRemindersJob::dispatchAfterResponse();

                Notification::make()
                    ->success()
                    ->title('Erinnerungsprüfung wurde gestartet.')
                    ->send();
            });
    }
}

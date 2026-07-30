<?php

namespace App\Filament\Admin\Pages;

use App\Services\NotificationTemplateService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MoveNotificationTemplates extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|null|\UnitEnum $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 31;

    protected static ?string $title = 'Umzugsmitteilungs-Templates';
    public string $emailSubject = '';
    public string $emailBody = '';
    public string $letterSubject = '';
    public string $letterBody = '';
    /**
     * @var array<int, string>
     */
    public array $placeholders = [];
    protected string $view = 'filament.admin.pages.move-notification-templates';

    public static function getNavigationLabel(): string
    {
        return 'Mitteilungs-Templates';
    }

    public function mount(NotificationTemplateService $service): void
    {
        $this->loadTemplates($service);
        $this->placeholders = $service->availablePlaceholders();
    }

    private function loadTemplates(NotificationTemplateService $service): void
    {
        $templates = $service->getMoveNotificationTemplates();

        $this->emailSubject = $templates['email']['subject'];
        $this->emailBody = $templates['email']['body'];
        $this->letterSubject = $templates['letter']['subject'];
        $this->letterBody = $templates['letter']['body'];
    }

    public function save(NotificationTemplateService $service): void
    {
        $this->validate([
            'emailSubject' => ['required', 'string', 'max:255'],
            'emailBody' => ['required', 'string'],
            'letterSubject' => ['required', 'string', 'max:255'],
            'letterBody' => ['required', 'string'],
        ]);

        $service->saveMoveNotificationTemplates([
            NotificationTemplateService::KEY_EMAIL_SUBJECT => $this->emailSubject,
            NotificationTemplateService::KEY_EMAIL_BODY => $this->emailBody,
            NotificationTemplateService::KEY_LETTER_SUBJECT => $this->letterSubject,
            NotificationTemplateService::KEY_LETTER_BODY => $this->letterBody,
        ]);

        Notification::make()
            ->title('Templates gespeichert.')
            ->success()
            ->send();
    }

    public function resetDefaults(NotificationTemplateService $service): void
    {
        $service->resetMoveNotificationTemplatesToDefaults();
        $this->loadTemplates($service);

        Notification::make()
            ->title('Standard-Templates wiederhergestellt.')
            ->warning()
            ->send();
    }
}


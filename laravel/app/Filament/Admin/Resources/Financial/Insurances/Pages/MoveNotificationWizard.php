<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Pages;

use App\Filament\Admin\Pages\MoveNotificationTemplates;
use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
use App\Models\Financial\Insurance;
use App\Models\User;
use App\Services\InsuranceMoveNotificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class MoveNotificationWizard extends Page
{
    protected static string $resource = InsuranceResource::class;
    public int $step = 1;
    /**
     * @var Collection<int, Insurance>
     */
    public Collection $insurances;
    /**
     * @var array{line1: string, line2: string, zip: string, city: string}
     */
    public array $oldAddress = [
        'line1' => '',
        'line2' => '',
        'zip' => '',
        'city' => '',
    ];
    /**
     * @var array{line1: string, line2: string, zip: string, city: string}
     */
    public array $newAddress = [
        'line1' => '',
        'line2' => '',
        'zip' => '',
        'city' => '',
    ];
    /**
     * @var array<int, int|string>
     */
    public array $selectedInsuranceIds = [];
    /**
     * @var array<string, string>
     */
    public array $channels = [];
    /**
     * @var array<string, array{subject?: string, body?: string, send?: bool}>
     */
    public array $drafts = [];
    /**
     * @var array<string, array<int, string>>
     */
    public array $warnings = [];

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Alte Adresse')
                        ->description('Die neue Adresse wird aus deinem Profil verwendet.')
                        ->beforeValidation(fn() => $this->validateStepOne())
                        ->schema([
                            View::make('filament.admin.resources.financial.insurances.pages.move-notification-wizard-step-1'),
                        ]),
                    Step::make('Versicherungen & Kanal')
                        ->description('Waehle Versicherungen und den Versandkanal je Versicherung.')
                        ->beforeValidation(fn() => $this->validateStepTwo())
                        ->afterValidation(function (): void {
                            $this->enforceChannelFallbacks();
                            app(InsuranceMoveNotificationService::class)->prepareDrafts(
                                insurances: $this->insurances,
                                selectedInsuranceIds: $this->selectedInsuranceIds,
                                channels: $this->channels,
                                drafts: $this->drafts,
                                oldAddress: $this->oldAddress,
                                newAddress: $this->newAddress,
                            );
                        })
                        ->schema([
                            View::make('filament.admin.resources.financial.insurances.pages.move-notification-wizard-step-2'),
                        ]),
                    Step::make('Vorschau & Versand')
                        ->description('Texte pruefen, optional bearbeiten und Versand bestaetigen.')
                        ->schema([
                            View::make('filament.admin.resources.financial.insurances.pages.move-notification-wizard-step-3'),
                        ]),
                ])
                    ->persistStepInQueryString('moveStep')
                    ->submitAction(new HtmlString(
                        '<x-filament::button type="button" wire:click="submit" icon="heroicon-o-paper-airplane">Mitteilungen verarbeiten</x-filament::button>'
                    )),
            ]);
    }

    public function mount(InsuranceMoveNotificationService $service): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $this->newAddress = $service->newAddressFromUser($user);

        $this->insurances = Insurance::query()
            ->where(Insurance::user_id, $user->id)
            ->orderBy(Insurance::name)
            ->get();
    }

    private function validateStepOne(): void
    {
        $this->validate([
            'oldAddress.line1' => ['required', 'string', 'max:255'],
            'oldAddress.line2' => ['nullable', 'string', 'max:255'],
            'oldAddress.zip' => ['required', 'string', 'max:50'],
            'oldAddress.city' => ['required', 'string', 'max:255'],
        ]);
    }

    private function validateStepTwo(): void
    {
        $this->validate([
            'selectedInsuranceIds' => ['required', 'array', 'min:1'],
        ]);
    }

    private function enforceChannelFallbacks(): void
    {
        $selected = $this->insurances
            ->whereIn(Insurance::id, $this->selectedInsuranceIds)
            ->keyBy(Insurance::id);

        foreach ($this->selectedInsuranceIds as $insuranceId) {
            $insurance = $selected->get((int)$insuranceId);

            if (!$insurance instanceof Insurance) {
                continue;
            }

            $key = (string)$insurance->id;
            $channel = (string)($this->channels[$key] ?? InsuranceMoveNotificationChannelEnum::default());
            $this->warnings[$key] = [];

            if ($channel === InsuranceMoveNotificationChannelEnum::EMAIL->name && blank($insurance->{Insurance::email})) {
                $this->channels[$key] = InsuranceMoveNotificationChannelEnum::BRIEF->name;
                $this->warnings[$key][] = 'E-Mail-Adresse fehlt. Versand wurde automatisch auf Brief umgestellt.';
            }

            if (blank($insurance->{Insurance::address_line_1}) && blank($insurance->{Insurance::address_city})) {
                $this->warnings[$key][] = 'Adresse der Versicherung ist unvollstaendig. Bitte Text im letzten Schritt pruefen.';
            }
        }
    }

    public function submit(InsuranceMoveNotificationService $service): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $this->validateStepOne();
        $this->validateStepTwo();
        $this->enforceChannelFallbacks();
        $service->prepareDrafts(
            insurances: $this->insurances,
            selectedInsuranceIds: $this->selectedInsuranceIds,
            channels: $this->channels,
            drafts: $this->drafts,
            oldAddress: $this->oldAddress,
            newAddress: $this->newAddress,
        );

        $result = $service->process(
            user: $user,
            selectedInsuranceIds: $this->selectedInsuranceIds,
            channelsByInsuranceId: $this->channels,
            draftsByInsuranceId: $this->drafts,
            oldAddress: $this->oldAddress,
        );

        foreach ($result['warnings'] as $warning) {
            Notification::make()
                ->title($warning)
                ->warning()
                ->send();
        }

        Notification::make()
            ->title('Umzugsmitteilungen verarbeitet.')
            ->body(sprintf(
                'Gesendet: %d, E-Mails: %d, Briefe: %d',
                $result['processed'],
                $result['emailed'],
                $result['letters']
            ))
            ->success()
            ->send();

        $this->redirect(InsuranceResource::getUrl('index'));
    }

    public function isSelectedInsurance(int $insuranceId): bool
    {
        return in_array($insuranceId, array_map('intval', $this->selectedInsuranceIds), true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_templates')
                ->label('Templates bearbeiten')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn(): string => MoveNotificationTemplates::getUrl()),
            Action::make('back_to_list')
                ->label('Zurueck zu Versicherungen')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => InsuranceResource::getUrl('index')),
        ];
    }
}


<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Pages;

use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
use App\Models\Financial\Insurance;
use App\Models\User;
use App\Services\InsuranceMoveNotificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

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
    protected string $view = 'filament.admin.resources.financial.insurances.pages.move-notification-wizard';

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

    public function nextStep(InsuranceMoveNotificationService $service): void
    {
        if ($this->step === 1) {
            $this->validateStepOne();
            $this->step = 2;

            return;
        }

        if ($this->step === 2) {
            $this->validateStepTwo();
            $this->enforceChannelFallbacks();
            $this->buildDrafts($service);
            $this->step = 3;
        }
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

    private function buildDrafts(InsuranceMoveNotificationService $service): void
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
            if (!isset($this->channels[$key]) || $this->channels[$key] === '') {
                $this->channels[$key] = InsuranceMoveNotificationChannelEnum::default();
            }

            $channel = (string)$this->channels[$key];

            if (!isset($this->drafts[$key])) {
                $this->drafts[$key] = $service->buildDraft($insurance, $this->oldAddress, $this->newAddress);
            }

            $this->drafts[$key]['send'] = (bool)($this->drafts[$key]['send'] ?? true);
            $this->channels[$key] = $channel;
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(InsuranceMoveNotificationService $service): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $this->validateStepOne();
        $this->validateStepTwo();
        $this->enforceChannelFallbacks();

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
            Action::make('back_to_list')
                ->label('Zurueck zu Versicherungen')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => InsuranceResource::getUrl('index')),
        ];
    }
}


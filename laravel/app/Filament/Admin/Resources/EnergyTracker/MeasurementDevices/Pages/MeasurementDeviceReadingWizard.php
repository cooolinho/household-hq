<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Services\EnergyTracker\ReadingEntryValueGuard;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class MeasurementDeviceReadingWizard extends Page
{
    protected static string $resource = MeasurementDeviceResource::class;
    public int $step = 0;
    public ?string $readingValue = null;
    public ?string $readingDate = null;
    /**
     * @var \Illuminate\Database\Eloquent\Collection<int, MeasurementDevice>
     */
    public $devices;
    protected string $view = 'filament.admin.resources.energy-tracker.measurement-devices.pages.measurement-device-reading-wizard';

    public function mount(): void
    {
        $this->devices = MeasurementDevice::query()
            ->where(MeasurementDevice::user_id, auth()->id())
            ->orderBy(MeasurementDevice::group)
            ->orderBy(MeasurementDevice::name)
            ->get();

        $this->setStep(0);
    }

    private function setStep(int $nextStep, bool $resetState = false): void
    {
        if ($this->devices->isEmpty()) {
            $this->step = 0;
            $this->readingValue = null;
            $this->readingDate = null;

            return;
        }

        $this->step = max(0, min($nextStep, $this->devices->count() - 1));

        if ($resetState || blank($this->readingDate)) {
            $this->readingDate = now()->format('Y-m-d\TH:i');
        }

        if ($resetState || blank($this->readingValue)) {
            $defaultValue = ReadingEntryValueGuard::defaultValue($this->getCurrentDevice());
            $this->readingValue = $defaultValue === null ? null : (string)$defaultValue;
        }

        $this->resetErrorBag();
    }

    public function getCurrentDevice(): ?MeasurementDevice
    {
        if ($this->devices->isEmpty()) {
            return null;
        }

        return $this->devices->get($this->step);
    }

    public function getTotalSteps(): int
    {
        return $this->devices->count();
    }

    public function previousStep(): void
    {
        $this->setStep($this->step - 1);
    }

    public function skipStep(): void
    {
        $this->setStep($this->step + 1);
    }

    public function saveAndContinue(): void
    {
        $device = $this->getCurrentDevice();

        if (!$device) {
            return;
        }

        $this->validate([
            'readingValue' => ['required', 'numeric'],
            'readingDate' => ['required', 'date'],
        ]);

        $validationError = ReadingEntryValueGuard::validateValue($device, (float)$this->readingValue);

        if ($validationError !== null) {
            $this->addError('readingValue', $validationError);

            return;
        }

        ReadingEntry::query()->create([
            ReadingEntry::measurement_device_id => $device->id,
            ReadingEntry::reading_value => $this->readingValue,
            ReadingEntry::reading_date => Carbon::parse($this->readingDate)->format('Y-m-d H:i:s'),
        ]);

        Notification::make()
            ->title(sprintf('Ablesung fuer "%s" gespeichert', $device->name))
            ->success()
            ->send();

        if ($this->step >= ($this->devices->count() - 1)) {
            Notification::make()
                ->title('Wizard abgeschlossen')
                ->body('Alle Messgeraete wurden durchlaufen.')
                ->success()
                ->send();

            $this->redirect(MeasurementDeviceResource::getUrl('index'));

            return;
        }

        $this->setStep($this->step + 1, resetState: true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Zur Messgeraete-Liste')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => MeasurementDeviceResource::getUrl('index')),
        ];
    }
}


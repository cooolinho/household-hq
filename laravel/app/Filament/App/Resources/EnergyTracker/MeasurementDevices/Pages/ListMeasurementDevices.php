<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages;

use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Services\EnergyTracker\ReadingEntryValueGuard;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ListMeasurementDevices extends ListRecords
{
    protected static string $resource = MeasurementDeviceResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.app.resources.energy-tracker.measurement-devices.pages.list-measurement-devices'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('wizard')
                ->label('Ablese-Wizard')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->url(fn(): string => MeasurementDeviceResource::getUrl('wizard')),
        ];
    }

    public function getMeasurementDevicesProperty(): Collection
    {
        return MeasurementDevice::query()
            ->where(MeasurementDevice::user_id, auth()->id())
            ->withCount(MeasurementDevice::has_many_reading_entries)
            ->orderBy(MeasurementDevice::group)
            ->orderBy(MeasurementDevice::name)
            ->get();
    }

    public function addReadingEntryAction(): Action
    {
        return Action::make('addReadingEntry')
            ->label('Ablesung erfassen')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->schema([
                TextInput::make(ReadingEntry::reading_value)
                    ->label('Zaehlerstand')
                    ->numeric()
                    ->required(),
                DateTimePicker::make(ReadingEntry::reading_date)
                    ->label('Ablesedatum')
                    ->default(now())
                    ->required(),
            ])
            ->fillForm(function (Action $action): array {
                $device = $this->resolveMeasurementDeviceFromActionArguments($action->getArguments());

                return [
                    ReadingEntry::reading_value => $device ? ReadingEntryValueGuard::defaultValue($device) : null,
                    ReadingEntry::reading_date => now(),
                ];
            })
            ->modalHeading(function (Action $action): string {
                $device = $this->resolveMeasurementDeviceFromActionArguments($action->getArguments());

                return $device
                    ? sprintf('Neue Ablesung fuer %s', $device->name)
                    : 'Neue Ablesung';
            })
            ->action(function (Action $action, array $data): void {
                $device = $this->resolveMeasurementDeviceFromActionArguments($action->getArguments());

                if (!$device) {
                    throw ValidationException::withMessages([
                        ReadingEntry::reading_value => 'Das Messgeraet wurde nicht gefunden.',
                    ]);
                }

                $validationError = ReadingEntryValueGuard::validateValue(
                    $device,
                    (float)$data[ReadingEntry::reading_value],
                );

                if ($validationError !== null) {
                    throw ValidationException::withMessages([
                        ReadingEntry::reading_value => $validationError,
                    ]);
                }

                ReadingEntry::query()->create([
                    ReadingEntry::measurement_device_id => $device->id,
                    ReadingEntry::reading_value => $data[ReadingEntry::reading_value],
                    ReadingEntry::reading_date => $data[ReadingEntry::reading_date],
                ]);

                Notification::make()
                    ->title('Ablesung gespeichert')
                    ->success()
                    ->send();
            });
    }

    private function resolveMeasurementDeviceFromActionArguments(array $arguments): ?MeasurementDevice
    {
        $deviceId = $arguments['deviceId'] ?? null;

        if (blank($deviceId)) {
            return null;
        }

        return MeasurementDevice::query()
            ->where(MeasurementDevice::id, $deviceId)
            ->where(MeasurementDevice::user_id, auth()->id())
            ->first();
    }
}

<?php

namespace App\Services\Reminder;

use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\MeasurementDeviceContractResource;
use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\App\Resources\Financial\Insurances\InsuranceResource;
use App\Filament\App\Resources\Inventory\Articles\ArticleResource;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use App\Models\Inventory\Article;
use App\Models\Inventory\Location;
use App\Models\User;

/**
 * Zentrale Definition aller Models, die Reminder unterstützen: Datums-Properties,
 * Empfänger-Auflösung und Ziel-URL. Ein neues Ziel-Model einzubinden bedeutet nur,
 * hier einen weiteren Eintrag zu ergänzen.
 */
readonly class ReminderTargetRegistry
{
    /**
     * @return array<string, string> Spalte => Label, für Filament-Selects
     */
    public function datePropertyOptions(?string $modelClass): array
    {
        return $this->forModel($modelClass)?->dateProperties ?? [];
    }

    public function forModel(?string $modelClass): ?ReminderTarget
    {
        if ($modelClass === null) {
            return null;
        }

        foreach ($this->all() as $target) {
            if ($target->modelClass === $modelClass) {
                return $target;
            }
        }

        return null;
    }

    /**
     * @return ReminderTarget[]
     */
    public function all(): array
    {
        return [
            $this->fixedCost(),
            $this->insurance(),
            $this->article(),
            $this->measurementDeviceContract(),
        ];
    }

    private function fixedCost(): ReminderTarget
    {
        return new ReminderTarget(
            modelClass: FixedCost::class,
            label: 'Fixkosten',
            dateProperties: [
                FixedCost::next_booking_date => 'Nächste Buchung',
                FixedCost::extended_date => 'Verlängerung',
                FixedCost::ends_date => 'Ende',
            ],
            userResolver: fn(FixedCost $fixedCost): ?User => $fixedCost->user,
            titleResolver: fn(FixedCost $fixedCost): string => (string)$fixedCost->{FixedCost::name},
            urlResolver: fn(FixedCost $fixedCost): ?string => $fixedCost->getKey()
                ? FixedCostResource::getUrl(FixedCostResource::PAGE_VIEW, ['record' => $fixedCost->getKey()])
                : null,
            optionsResolver: fn(): array => FixedCost::query()
                ->where(FixedCost::user_id, auth()->id())
                ->orderBy(FixedCost::name)
                ->pluck(FixedCost::name, FixedCost::id)
                ->all(),
        );
    }

    private function insurance(): ReminderTarget
    {
        return new ReminderTarget(
            modelClass: Insurance::class,
            label: 'Versicherung',
            dateProperties: [
                Insurance::start_date => 'Beginn',
                Insurance::end_date => 'Ende',
            ],
            userResolver: fn(Insurance $insurance): ?User => $insurance->user,
            titleResolver: fn(Insurance $insurance): string => (string)$insurance->{Insurance::name},
            urlResolver: fn(Insurance $insurance): ?string => $insurance->getKey()
                ? InsuranceResource::getUrl(InsuranceResource::PAGE_VIEW, ['record' => $insurance->getKey()])
                : null,
            optionsResolver: fn(): array => Insurance::query()
                ->where(Insurance::user_id, auth()->id())
                ->orderBy(Insurance::name)
                ->pluck(Insurance::name, Insurance::id)
                ->all(),
        );
    }

    private function article(): ReminderTarget
    {
        return new ReminderTarget(
            modelClass: Article::class,
            label: 'Artikel',
            dateProperties: [
                Article::purchase_date => 'Kaufdatum',
                Article::warranty_until => 'Garantie bis',
                Article::sold_at => 'Verkaufsdatum',
            ],
            userResolver: fn(Article $article): ?User => $article->{Article::belongs_to_location}
                ?->{Location::belongs_to_collection}
                ?->user,
            titleResolver: fn(Article $article): string => (string)$article->{Article::name},
            urlResolver: fn(Article $article): ?string => $article->getKey()
                ? ArticleResource::getUrl('view', ['record' => $article->getKey()])
                : null,
            optionsResolver: fn(): array => Article::query()
                ->whereHas(
                    Article::belongs_to_location . '.' . Location::belongs_to_collection,
                    fn($query) => $query->where('user_id', auth()->id()),
                )
                ->orderBy(Article::name)
                ->pluck(Article::name, Article::id)
                ->all(),
        );
    }

    private function measurementDeviceContract(): ReminderTarget
    {
        return new ReminderTarget(
            modelClass: MeasurementDeviceContract::class,
            label: 'Vertrag',
            dateProperties: [
                MeasurementDeviceContract::starts_on => 'Beginn',
                MeasurementDeviceContract::ends_on => 'Ende',
            ],
            userResolver: fn(MeasurementDeviceContract $contract): ?User => $contract
                ->{MeasurementDeviceContract::belongs_to_measurement_device}
                ?->{MeasurementDevice::belongs_to_user},
            titleResolver: fn(MeasurementDeviceContract $contract): string => (string)$contract->{MeasurementDeviceContract::name},
            urlResolver: fn(MeasurementDeviceContract $contract): ?string => $contract->getKey()
                ? MeasurementDeviceContractResource::getUrl('view', ['record' => $contract->getKey()])
                : null,
            optionsResolver: fn(): array => MeasurementDeviceContract::query()
                ->whereHas(
                    MeasurementDeviceContract::belongs_to_measurement_device,
                    fn($query) => $query->where(MeasurementDevice::user_id, auth()->id()),
                )
                ->orderBy(MeasurementDeviceContract::name)
                ->pluck(MeasurementDeviceContract::name, MeasurementDeviceContract::id)
                ->all(),
        );
    }

    /**
     * @return array<int, string> Datensatz-ID => Label, gescoped auf den aktuellen Benutzer
     */
    public function modelOptions(?string $modelClass): array
    {
        return $this->forModel($modelClass)?->options() ?? [];
    }

    /**
     * @return array<string, string> Model-Klasse => Label, für Filament-Selects
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->all() as $target) {
            $options[$target->modelClass] = $target->label;
        }

        return $options;
    }
}

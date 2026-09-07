<?php

namespace App\Filament\App\Resources\Documents\Support;

use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\MeasurementDeviceContractResource;
use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\App\Resources\Financial\Insurances\InsuranceResource;
use App\Filament\App\Resources\Financial\Transactions\TransactionResource;
use App\Filament\App\Resources\Inventory\Articles\ArticleResource;
use App\Models\Contracts\Documentables;
use App\Models\Document;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use App\Models\Financial\Transaction;
use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class DocumentOwnerRegistry
{
    const string TYPE_FIXED_COST = 'fixed_cost';
    const string TYPE_INSURANCE = 'insurance';
    const string TYPE_ARTICLE = 'article';
    const string TYPE_MEASUREMENT_DEVICE_CONTRACT = 'measurement_device_contract';
    const string TYPE_TRANSACTION = 'transaction';

    public static function getRelationshipName(Model|string $owner): ?string
    {
        return match (self::getOwnerClass($owner)) {
            Insurance::class => Insurance::has_many_documents,
            FixedCost::class => FixedCost::has_many_documents,
            Article::class => Article::has_many_documents,
            MeasurementDeviceContract::class => MeasurementDeviceContract::has_many_documents,
            Transaction::class => Transaction::has_many_documents,
            default => null,
        };
    }

    private static function getOwnerClass(Model|string $owner): string
    {
        return $owner instanceof Model ? $owner::class : $owner;
    }

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_FIXED_COST => self::getLabel(FixedCost::class),
            self::TYPE_INSURANCE => self::getLabel(Insurance::class),
            self::TYPE_ARTICLE => self::getLabel(Article::class),
            self::TYPE_MEASUREMENT_DEVICE_CONTRACT => self::getLabel(MeasurementDeviceContract::class),
            self::TYPE_TRANSACTION => self::getLabel(Transaction::class),
        ];
    }

    public static function getOwnerClassByType(?string $type): ?string
    {
        return match ($type) {
            self::TYPE_FIXED_COST => FixedCost::class,
            self::TYPE_INSURANCE => Insurance::class,
            self::TYPE_ARTICLE => Article::class,
            self::TYPE_MEASUREMENT_DEVICE_CONTRACT => MeasurementDeviceContract::class,
            self::TYPE_TRANSACTION => Transaction::class,
            default => null,
        };
    }

    public static function getTypeByOwner(Model|string $owner): ?string
    {
        return match (self::getOwnerClass($owner)) {
            FixedCost::class => self::TYPE_FIXED_COST,
            Insurance::class => self::TYPE_INSURANCE,
            Article::class => self::TYPE_ARTICLE,
            MeasurementDeviceContract::class => self::TYPE_MEASUREMENT_DEVICE_CONTRACT,
            Transaction::class => self::TYPE_TRANSACTION,
            default => null,
        };
    }

    public static function getResourceClass(Model|string $owner): ?string
    {
        return match (self::getOwnerClass($owner)) {
            Insurance::class => InsuranceResource::class,
            FixedCost::class => FixedCostResource::class,
            Article::class => ArticleResource::class,
            MeasurementDeviceContract::class => MeasurementDeviceContractResource::class,
            Transaction::class => TransactionResource::class,
            default => null,
        };
    }

    public static function canAccess(Model $owner): bool
    {
        return match ($owner::class) {
            Insurance::class => InsuranceResource::canView($owner),
            FixedCost::class => FixedCostResource::canView($owner),
            MeasurementDeviceContract::class => MeasurementDeviceContractResource::canView($owner),
            Article::class => Article::query()
                ->whereKey($owner->getKey())
                ->whereHas(
                    Article::belongs_to_location . '.' . Location::belongs_to_collection,
                    fn(Builder $query) => $query->where(Collection::user_id, auth()->id())
                )
                ->exists(),
            Transaction::class => TransactionResource::canView($owner),
            default => false,
        };
    }

    public static function getDocumentContextLabel(Document $document): ?string
    {
        $firstLink = self::getLinkedOwners($document)->first();
        if (!$firstLink) {
            return null;
        }

        return $firstLink['label'];
    }

    public static function getOwnerContextLabel(Model $owner): ?string
    {
        $label = self::getLabel($owner);

        if (!filled($label)) {
            return null;
        }

        return sprintf('%s: %s', $label, self::getOwnerDisplayName($owner));
    }

    public static function getLabel(Model|string $owner): ?string
    {
        return match (self::getOwnerClass($owner)) {
            Insurance::class => 'Versicherung',
            FixedCost::class => 'Fixkosten',
            Article::class => 'Artikel',
            MeasurementDeviceContract::class => 'Vertrag',
            Transaction::class => 'Transaktion',
            default => null,
        };
    }

    public static function getOwnerDisplayName(Model $owner): string
    {
        $displayName = match ($owner::class) {
            Insurance::class => $owner->{Insurance::name},
            FixedCost::class => $owner->{FixedCost::name},
            Article::class => $owner->{Article::name},
            MeasurementDeviceContract::class => $owner->{MeasurementDeviceContract::name},
            Transaction::class => $owner->{Transaction::purpose}
                ?: ($owner->{Transaction::payer} ?: sprintf('Transaktion #%s', $owner->getKey())),
            default => null,
        };

        if (filled($displayName)) {
            return (string)$displayName;
        }

        return sprintf('#%s', $owner->getKey());
    }

    public static function getDocumentContextUrl(Document $document): ?string
    {
        $firstLink = self::getLinkedOwners($document)->first();
        if (!$firstLink) {
            return null;
        }

        return $firstLink['url'];
    }

    public static function getOwnerViewUrl(Model $owner): ?string
    {
        return match ($owner::class) {
            Insurance::class => InsuranceResource::getUrl('view', ['record' => $owner->getKey()]),
            FixedCost::class => FixedCostResource::getUrl('view', ['record' => $owner->getKey()]),
            Article::class => ArticleResource::getUrl('view', ['record' => $owner->getKey()]),
            MeasurementDeviceContract::class => MeasurementDeviceContractResource::getUrl('view', ['record' => $owner->getKey()]),
            Transaction::class => TransactionResource::getUrl('view', ['record' => $owner->getKey()]),
            default => null,
        };
    }

    public static function getLinkedOwners(Document $document): SupportCollection
    {
        $document->loadMissing([
            Document::morphed_by_many_fixed_costs,
            Document::morphed_by_many_insurances,
            Document::morphed_by_many_articles,
        ]);

        if ($document->exists) {
            $document->loadMissing([
                Document::morphed_by_many_measurement_device_contracts,
                Document::morphed_by_many_transactions,
            ]);
        }

        $fixedCosts = self::mapOwners($document->{Document::morphed_by_many_fixed_costs});
        $insurances = self::mapOwners($document->{Document::morphed_by_many_insurances});
        $articles = self::mapOwners($document->{Document::morphed_by_many_articles});
        $contracts = $document->relationLoaded(Document::morphed_by_many_measurement_device_contracts)
            ? self::mapOwners($document->{Document::morphed_by_many_measurement_device_contracts})
            : collect();
        $transactions = $document->relationLoaded(Document::morphed_by_many_transactions)
            ? self::mapOwners($document->{Document::morphed_by_many_transactions})
            : collect();

        return $fixedCosts
            ->merge($insurances)
            ->merge($articles)
            ->merge($contracts)
            ->merge($transactions)
            ->sortBy('label')
            ->values();
    }

    public static function getDocumentLinksCount(Document $document): int
    {
        $fixedCostsCount = $document->getAttribute('linked_fixed_costs_count');
        $insurancesCount = $document->getAttribute('linked_insurances_count');
        $articlesCount = $document->getAttribute('linked_articles_count');
        $contractsCount = $document->getAttribute('linked_measurement_device_contracts_count');
        $transactionsCount = $document->getAttribute('linked_transactions_count');

        if ($fixedCostsCount !== null || $insurancesCount !== null || $articlesCount !== null || $contractsCount !== null || $transactionsCount !== null) {
            return (int)$fixedCostsCount + (int)$insurancesCount + (int)$articlesCount + (int)$contractsCount + (int)$transactionsCount;
        }

        if (!$document->exists) {
            return self::getLinkedOwners($document)->count();
        }

        return (int)DB::table(Documentables::TABLE)
            ->where(Document::documentable_document_id, $document->getKey())
            ->count();
    }

    public static function getLinkedOwnerOptions(Document $document): array
    {
        return self::getLinkedOwners($document)
            ->mapWithKeys(fn(array $link): array => [
                $link['key'] => $link['label'],
            ])
            ->all();
    }

    public static function getOwnerOptionsForType(Document $document, ?string $type): array
    {
        $ownerClass = self::getOwnerClassByType($type);
        if (!$ownerClass) {
            return [];
        }

        $linkedIds = DB::table(Documentables::TABLE)
            ->where(Document::documentable_document_id, $document->getKey())
            ->where(Document::documentable_type, $ownerClass)
            ->pluck(Document::documentable_id)
            ->all();

        return self::buildOwnerQuery($ownerClass)
            ->when(!empty($linkedIds), fn(Builder $query) => $query->whereNotIn('id', $linkedIds))
            ->orderBy('id', 'desc')
            ->get()
            ->mapWithKeys(fn(Model $owner): array => [
                $owner->getKey() => self::getOwnerDisplayName($owner),
            ])
            ->all();
    }

    public static function detachLinkByKey(Document $document, string $linkKey): bool
    {
        [$type, $ownerId] = array_pad(explode(':', $linkKey, 2), 2, null);
        $ownerClass = self::getOwnerClassByType($type);
        if (!$ownerClass || !filled($ownerId)) {
            return false;
        }

        $owner = self::buildOwnerQuery($ownerClass)->find($ownerId);
        if (!$owner instanceof Model) {
            return false;
        }

        $relationshipName = self::getRelationshipName($owner);
        if (!$relationshipName) {
            return false;
        }

        $owner->{$relationshipName}()->detach($document->getKey());

        return true;
    }

    public static function attachOwner(Document $document, ?string $type, int|string|null $ownerId): bool
    {
        $ownerClass = self::getOwnerClassByType($type);
        if (!$ownerClass || !filled($ownerId)) {
            return false;
        }

        $owner = self::buildOwnerQuery($ownerClass)->find($ownerId);
        if (!$owner instanceof Model) {
            return false;
        }

        $relationshipName = self::getRelationshipName($owner);
        if (!$relationshipName) {
            return false;
        }

        $owner->{$relationshipName}()->syncWithoutDetaching([$document->getKey()]);

        return true;
    }

    private static function mapOwners(EloquentCollection $owners): SupportCollection
    {
        return collect($owners->all())
            ->map(function (Model $owner): array {
                $type = self::getTypeByOwner($owner);

                return [
                    'key' => sprintf('%s:%s', $type, $owner->getKey()),
                    'label' => self::getOwnerContextLabel($owner),
                    'url' => self::getOwnerViewUrl($owner),
                    'type' => $type,
                    'owner_id' => (string)$owner->getKey(),
                ];
            })
            ->filter(fn(array $link): bool => filled($link['label']) && filled($link['type']))
            ->values();
    }

    private static function buildOwnerQuery(string $ownerClass): Builder
    {
        return match ($ownerClass) {
            Insurance::class => Insurance::query()->where(Insurance::user_id, auth()->id()),
            FixedCost::class => FixedCost::query()->where(FixedCost::user_id, auth()->id()),
            Article::class => Article::query()->whereHas(
                Article::belongs_to_location . '.' . Location::belongs_to_collection,
                fn(Builder $query) => $query->where(Collection::user_id, auth()->id())
            ),
            MeasurementDeviceContract::class => MeasurementDeviceContract::query()->whereHas(
                MeasurementDeviceContract::belongs_to_measurement_device,
                fn(Builder $query) => $query->where(MeasurementDevice::user_id, auth()->id())
            ),
            Transaction::class => Transaction::query()->where(Transaction::user_id, auth()->id()),
            default => Insurance::query()->whereRaw('1 = 0'),
        };
    }
}

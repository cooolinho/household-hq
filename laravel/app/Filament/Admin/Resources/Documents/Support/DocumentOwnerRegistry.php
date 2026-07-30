<?php

namespace App\Filament\Admin\Resources\Documents\Support;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use App\Models\Document;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use App\Models\Inventory\Article;
use Illuminate\Database\Eloquent\Model;

class DocumentOwnerRegistry
{
    public static function getRelationshipName(Model|string $owner): ?string
    {
        return match (self::getOwnerClass($owner)) {
            Insurance::class => Insurance::has_many_documents,
            FixedCost::class => FixedCost::has_many_documents,
            Article::class => Article::has_many_documents,
            default => null,
        };
    }

    private static function getOwnerClass(Model|string $owner): string
    {
        return $owner instanceof Model ? $owner::class : $owner;
    }

    public static function getResourceClass(Model|string $owner): ?string
    {
        return match (self::getOwnerClass($owner)) {
            Insurance::class => InsuranceResource::class,
            FixedCost::class => FixedCostResource::class,
            Article::class => ArticleResource::class,
            default => null,
        };
    }

    public static function canAccess(Model $owner): bool
    {
        return match ($owner::class) {
            Insurance::class => InsuranceResource::canView($owner),
            FixedCost::class => FixedCostResource::canView($owner),
            Article::class => ArticleResource::canView($owner),
            default => false,
        };
    }

    public static function getDocumentContextLabel(Document $document): ?string
    {
        $owner = $document->documentable;

        if (!$owner instanceof Model) {
            return null;
        }

        return self::getOwnerContextLabel($owner);
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
            default => null,
        };
    }

    public static function getOwnerDisplayName(Model $owner): string
    {
        $displayName = match ($owner::class) {
            Insurance::class => $owner->{Insurance::name},
            FixedCost::class => $owner->{FixedCost::name},
            Article::class => $owner->{Article::name},
            default => null,
        };

        if (filled($displayName)) {
            return (string)$displayName;
        }

        return sprintf('#%s', $owner->getKey());
    }

    public static function getDocumentContextUrl(Document $document): ?string
    {
        $owner = $document->documentable;

        if (!$owner instanceof Model) {
            return null;
        }

        return self::getOwnerViewUrl($owner);
    }

    public static function getOwnerViewUrl(Model $owner): ?string
    {
        return match ($owner::class) {
            Insurance::class => InsuranceResource::getUrl('view', ['record' => $owner->getKey()]),
            FixedCost::class => FixedCostResource::getUrl('view', ['record' => $owner->getKey()]),
            Article::class => ArticleResource::getUrl('view', ['record' => $owner->getKey()]),
            default => null,
        };
    }
}


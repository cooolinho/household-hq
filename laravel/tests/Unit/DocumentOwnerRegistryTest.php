<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Models\Document;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use App\Models\Financial\Transaction;
use App\Models\Inventory\Article;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DocumentOwnerRegistryTest extends TestCase
{
    public function test_it_resolves_relationship_names_and_labels(): void
    {
        $insurance = new Insurance([
            Insurance::name => 'Hausrat',
            Insurance::user_id => 1,
        ]);

        $fixedCost = new FixedCost([
            FixedCost::name => 'Miete',
            FixedCost::user_id => 1,
            FixedCost::amount => -1200,
            FixedCost::category_id => null,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE->name,
        ]);

        $article = new Article([
            Article::name => 'Werkbank',
        ]);

        $this->assertSame(Insurance::has_many_documents, DocumentOwnerRegistry::getRelationshipName($insurance));
        $this->assertSame(FixedCost::has_many_documents, DocumentOwnerRegistry::getRelationshipName($fixedCost));
        $this->assertSame(Article::has_many_documents, DocumentOwnerRegistry::getRelationshipName($article));

        $this->assertSame('Versicherung: Hausrat', DocumentOwnerRegistry::getOwnerContextLabel($insurance));
        $this->assertSame('Fixkosten: Miete', DocumentOwnerRegistry::getOwnerContextLabel($fixedCost));
        $this->assertSame('Artikel: Werkbank', DocumentOwnerRegistry::getOwnerContextLabel($article));
    }

    public function test_it_allows_access_for_the_authenticated_owner(): void
    {
        Auth::shouldReceive('id')->andReturn(77);

        $insurance = new Insurance([
            Insurance::name => 'Hausrat',
            Insurance::user_id => 77,
        ]);

        $fixedCost = new FixedCost([
            FixedCost::user_id => 77,
            FixedCost::name => 'Miete',
            FixedCost::amount => -1200,
            FixedCost::category_id => null,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE->name,
        ]);

        $this->assertTrue(DocumentOwnerRegistry::canAccess($insurance));
        $this->assertTrue(DocumentOwnerRegistry::canAccess($fixedCost));
    }

    public function test_it_resolves_transaction_document_ownership(): void
    {
        $transaction = new Transaction([
            Transaction::purpose => 'Quittung',
            Transaction::user_id => 77,
        ]);

        $this->assertSame(Transaction::has_many_documents, DocumentOwnerRegistry::getRelationshipName($transaction));
        $this->assertSame('Transaktion: Quittung', DocumentOwnerRegistry::getOwnerContextLabel($transaction));
    }

    public function test_it_summarizes_document_context_when_loaded(): void
    {
        $insurance = (new Insurance([
            Insurance::name => 'Hausrat',
            Insurance::user_id => 77,
        ]))->forceFill(['id' => 42]);

        $document = new Document([
            Document::user_id => 77,
            Document::path => 'sample.pdf',
            Document::filename => 'sample.pdf',
            Document::sort => 0,
        ]);

        $document->setRelation(Document::morphed_by_many_fixed_costs, new EloquentCollection());
        $document->setRelation(Document::morphed_by_many_insurances, new EloquentCollection([$insurance]));
        $document->setRelation(Document::morphed_by_many_articles, new EloquentCollection());

        $this->assertSame('Versicherung: Hausrat', DocumentOwnerRegistry::getDocumentContextLabel($document));
        $this->assertNotNull(DocumentOwnerRegistry::getDocumentContextUrl($document));
        $this->assertSame(1, DocumentOwnerRegistry::getDocumentLinksCount($document));
    }
}

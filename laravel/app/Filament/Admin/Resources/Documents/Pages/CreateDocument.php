<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Admin\Resources\Insurances\RelationManagers\DocumentsRelationManager;
use App\Filament\Admin\Resources\Inventory\Articles\RelationManagers\DocumentsRelationManager as ArticleRelationManager;
use App\Models\Document;
use App\Models\Insurance;
use App\Models\Inventory\Article;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Document::user_id] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Document $document */
        $document = parent::handleRecordCreation($data);

        if (!isset($data[DocumentForm::QUERY_PARAMS])) {
            return $document;
        }

        $queryParams = $data[DocumentForm::QUERY_PARAMS];
        if (isset($queryParams[DocumentsRelationManager::QUERY_PARAM_INSURANCE_ID])) {
            $insuranceId = (int)$queryParams[DocumentsRelationManager::QUERY_PARAM_INSURANCE_ID];
            $this->addDocumentToInsurance($document, $insuranceId);
        }

        if (isset($queryParams[ArticleRelationManager::QUERY_PARAM_ARTICLE_ID])) {
            $articleId = (int)$queryParams[ArticleRelationManager::QUERY_PARAM_ARTICLE_ID];
            $this->addDocumentToArticle($document, $articleId);
        }

        return $document;
    }

    private function addDocumentToInsurance(Document $document, int $insuranceId): void
    {
        $insurance = Insurance::query()->find($insuranceId);
        if (!$insurance instanceof Insurance) {
            return;
        }

        if ($insurance->user_id !== $document->user_id) {
            return;
        }

        $insurance->documents()->save($document);
    }

    private function addDocumentToArticle(Document $document, int $articleId): void
    {
        $article = Article::query()->find($articleId);
        if (!$article instanceof Article) {
            return;
        }

        $article->documents()->save($document);
    }
}

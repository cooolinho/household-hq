<?php

namespace App\Filament\Admin\Resources\Documents\Tables;

use App\Filament\Admin\Resources\Documents\Actions\ManageDocumentLinksAction;
use App\Filament\Admin\Resources\Documents\Support\DocumentOwnerRegistry;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(Document::created_at, 'desc')
            ->modifyQueryUsing(fn($query) => $query->withCount([
                Document::morphed_by_many_fixed_costs,
                Document::morphed_by_many_insurances,
                Document::morphed_by_many_articles,
                Document::morphed_by_many_measurement_device_contracts,
                Document::morphed_by_many_transactions,
            ]))
            ->columns(self::getTableColumns())
            ->filters(self::getFilters())
            ->recordActions([
                ActionGroup::make([
                    self::previewAction(),
                    self::downloadAction(),
                    ManageDocumentLinksAction::make(),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array
     */
    private static function getTableColumns(): array
    {
        return [
            TextColumn::make(Document::download_filename)
                ->label(__('admin.resource.document.fields.download_filename'))
                ->placeholder(__('admin.resource.document.placeholders.empty'))
                ->searchable(),
            TextColumn::make(Document::filename)
                ->label('Datei')
                ->searchable(),
            TextColumn::make(Document::type)
                ->label(__('admin.resource.document.fields.type'))
                ->searchable(),
            TextColumn::make('document_links_count')
                ->label('Verknüpft mit')
                ->state(fn(Document $record): int => DocumentOwnerRegistry::getDocumentLinksCount($record))
                ->badge(),
            TextColumn::make(Document::description)
                ->label(__('admin.resource.document.fields.description'))
                ->limit(60)
                ->placeholder(__('admin.resource.document.placeholders.empty'))
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make(Document::created_at)
                ->label(__('admin.resource.document.fields.created_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Aggregates all filters.
     *
     * @return array
     */
    private static function getFilters(): array
    {
        return [
            self::filterByType(),
            self::filterByMimeCategory(),
            self::filterByExtension(),
            self::filterByFileSizeRange(),
            self::filterBySort(),
        ];
    }

    private static function filterByType(): SelectFilter
    {
        return SelectFilter::make(Document::type)
            ->label(__('admin.resource.document.filters.type'))
            ->options(fn() => Document::query()
                ->whereNotNull(Document::type)
                ->distinct()
                ->pluck(Document::type, Document::type)
                ->toArray())
            ->placeholder(__('admin.resource.document.filters.placeholder_all'))
            ->searchable();
    }

    private static function filterByMimeCategory(): SelectFilter
    {
        $options = [
            'image' => __('admin.resource.document.mime_categories.image'),
            'pdf' => __('admin.resource.document.mime_categories.pdf'),
            'text' => __('admin.resource.document.mime_categories.text'),
            'other' => __('admin.resource.document.mime_categories.other'),
        ];

        return SelectFilter::make('mime_category')
            ->label(__('admin.resource.document.filters.mime_category'))
            ->options($options)
            ->query(function ($query, $value) {
                switch ($value) {
                    case 'image':
                        $query->where(Document::mime_type, 'like', 'image/%');
                        break;
                    case 'pdf':
                        $query->where(Document::mime_type, 'application/pdf');
                        break;
                    case 'text':
                        $query->where(Document::mime_type, 'like', 'text/%');
                        break;
                    case 'other':
                        $query->where(function ($q) {
                            $q->whereNull(Document::mime_type)
                                ->orWhere(Document::mime_type, 'not like', 'image/%')
                                ->where(Document::mime_type, 'not like', 'text/%')
                                ->where(Document::mime_type, '<>', 'application/pdf');
                        });
                        break;
                }

                return $query;
            });
    }

    private static function filterByExtension(): SelectFilter
    {
        return SelectFilter::make('extension')
            ->label(__('admin.resource.document.filters.extension'))
            ->multiple()
            ->options(fn() => Document::query()
                ->whereNotNull(Document::filename)
                ->get()
                ->map(fn(Document $document): string => pathinfo((string)$document->filename, PATHINFO_EXTENSION))
                ->filter()
                ->unique()
                ->mapWithKeys(fn(string $extension): array => [$extension => $extension])
                ->toArray());
    }

    private static function filterByFileSizeRange(): SelectFilter
    {
        // quick presets for file size ranges
        $options = [
            'tiny' => '< 10 KB',
            'small' => '< 100 KB',
            'medium' => '100 KB - 1 MB',
            'large' => '> 1 MB',
            'unknown' => 'Unbekannt (keine Metadaten)'
        ];

        return SelectFilter::make('file_size_range')
            ->label(__('admin.resource.document.filters.file_size'))
            ->options($options)
            ->query(function ($query, $value) {
                switch ($value) {
                    case 'tiny':
                        $query->where(Document::file_size, '<', 10 * 1024);
                        break;
                    case 'small':
                        $query->where(Document::file_size, '<', 100 * 1024)
                            ->where(Document::file_size, '>=', 10 * 1024);
                        break;
                    case 'medium':
                        $query->where(Document::file_size, '>=', 100 * 1024)
                            ->where(Document::file_size, '<=', 1024 * 1024);
                        break;
                    case 'large':
                        $query->where(Document::file_size, '>', 1024 * 1024);
                        break;
                    case 'unknown':
                        $query->whereNull(Document::file_size);
                        break;
                }

                return $query;
            });
    }

    private static function filterBySort(): Filter
    {
        return Filter::make(Document::sort)
            ->label(__('admin.resource.document.filters.sort'));
    }

    /**
     * Action für Dokumentvorschau im Modal
     */
    private static function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Vorschau')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->modalHeading(fn(Document $record) => $record->filename ?? 'Dokument Vorschau')
            ->modalContent(fn(Document $record) => view('filament.admin.resources.documents.modals.preview-content', [
                'document' => $record,
            ]))
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen');
    }

    /**
     * Action für Dokumenten-Download
     */
    private static function downloadAction(): Action
    {
        return Action::make('download')
            ->label('Download')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->url(fn(Document $record) => route('admin.documents.download', $record))
            ->openUrlInNewTab();
    }

    public function manageLinks()
    {

    }
}

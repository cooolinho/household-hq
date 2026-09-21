<?php

namespace App\Filament\App\Resources\Financial\Transactions\Actions;

use App\Filament\App\Resources\Tags\Schemas\TagForm;
use App\Models\Contracts\Taggables;
use App\Models\Financial\Transaction;
use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class TagTransactionAction
{
    public static function make(): Action
    {
        return Action::make('tagTransaction')
            ->label('Tags verwalten')
            ->icon(Heroicon::OutlinedTag)
            ->schema(self::form())
            ->fillForm(self::fillForm())
            ->modalHeading('Tags verwalten')
            ->modalDescription(fn(Transaction $record): HtmlString => new HtmlString(
                view('filament.app.resources.financial.transactions.actions.transaction-statement', [
                    'record' => $record,
                ])->render()
            ))
            ->modalWidth(Width::ExtraLarge)
            ->action(self::action());
    }

    private static function action(): \Closure
    {
        return function (Transaction $record, array $data): void {
            abort_unless((int)$record->{Transaction::user_id} === (int)auth()->id(), 403);

            $tagIds = self::getScopedTagsQuery((int)$record->{Transaction::user_id})
                ->whereIn(Tag::TABLE . '.' . Tag::id, array_map(fn(mixed $value): int => (int)$value, $data['tags'] ?? []))
                ->pluck(Tag::TABLE . '.' . Tag::id)
                ->map(fn(mixed $id): int => (int)$id)
                ->all();

            $record->tags()->sync($tagIds);

            Notification::make()
                ->title('Tags gespeichert')
                ->success()
                ->send();
        };
    }

    private static function fillForm(): \Closure
    {
        return function (Transaction $record): array {
            return [
                'tags' => $record->tags()->pluck(Tag::id)->map(fn(mixed $id): int => (int)$id)->all(),
            ];
        };
    }

    private static function form(): array
    {
        return [
            Select::make('tags')
                ->label('Tags')
                ->multiple()
                ->options(fn(): array => self::getTagOptions())
                ->searchable()
                ->preload()
                ->createOptionForm(TagForm::configure(new Schema)->getComponents())
                ->createOptionUsing(function (array $data): int {
                    $tag = Tag::query()->create([
                        ...$data,
                        Tag::user_id => auth()->id(),
                    ]);

                    return (int)$tag->getKey();
                })
                ->placeholder('Tags auswählen...')
                ->helperText('Top-Tags können oben per Klick direkt hinzugefügt werden.'),
        ];
    }

    private static function getTagOptions(): array
    {
        return self::getScopedTagsQuery((int)auth()->id())
            ->orderBy(Tag::TABLE . '.' . Tag::order_column)
            ->orderBy(Tag::TABLE . '.' . Tag::id)
            ->get()
            ->mapWithKeys(fn(Tag $tag): array => [
                (int)$tag->getKey() => (string)$tag->name,
            ])
            ->all();
    }

    /**
     * not implemented yet, but could be used to show top tags in the select field
     *
     * @return Collection<int, array{id: int, name: string, transactions_count: int}>
     */
    private static function getTopTags(int $userId): Collection
    {
        return self::getScopedTagsQuery($userId)
            ->select(Tag::TABLE . '.' . Tag::id, Tag::TABLE . '.' . Tag::name)
            ->selectRaw('COUNT(DISTINCT ' . Transaction::TABLE . '.' . Transaction::id . ') as transactions_count')
            ->join(Taggables::TABLE, Taggables::TABLE . '.' . Taggables::tag_id, '=', Tag::TABLE . '.' . Tag::id)
            ->join(Transaction::TABLE, function ($join): void {
                $join->on(Transaction::TABLE . '.' . Transaction::id, '=', Taggables::TABLE . '.' . Taggables::taggable_id)
                    ->where(Taggables::TABLE . '.' . Taggables::taggable_type, Transaction::class);
            })
            ->where(Tag::TABLE . '.' . Tag::user_id, $userId)
            ->where(Transaction::TABLE . '.' . Transaction::user_id, $userId)
            ->groupBy(Tag::TABLE . '.' . Tag::id, Tag::TABLE . '.' . Tag::name)
            ->orderByDesc('transactions_count')
            ->orderBy(Tag::TABLE . '.' . Tag::order_column)
            ->orderBy(Tag::TABLE . '.' . Tag::id)
            ->limit(5)
            ->get()
            ->map(fn(Tag $tag): array => [
                'id' => (int)$tag->getKey(),
                'name' => (string)$tag->name,
                'transactions_count' => (int)$tag->transactions_count,
            ]);
    }

    private static function getScopedTagsQuery(int $userId): Builder
    {
        return Tag::query()
            ->where(Tag::TABLE . '.' . Tag::user_id, $userId);
    }
}

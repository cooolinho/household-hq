<?php

namespace App\Filament\Admin\Resources\Financial\MatchingSuggestions;

use App\Filament\Admin\Resources\Financial\MatchingSuggestions\Pages\ListMatchingSuggestions;
use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionMatchingSuggestion;
use App\Services\FixedCostMatchingLearningService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MatchingSuggestionResource extends Resource
{
    protected static ?string $model = TransactionMatchingSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;
    protected static string|null|\UnitEnum $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 55;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.matching_suggestion.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.matching_suggestion.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.matching_suggestion.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = TransactionMatchingSuggestion::query()
            ->where(TransactionMatchingSuggestion::status, MatchingSuggestionStatusEnum::PENDING->name)
            ->whereHas(
                TransactionMatchingSuggestion::belongs_to_transaction,
                fn(Builder $q) => $q->where(Transaction::user_id, auth()->id())
            )
            ->count();

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->with([
                    TransactionMatchingSuggestion::belongs_to_transaction,
                    TransactionMatchingSuggestion::belongs_to_fixed_cost,
                ])
                ->whereHas(
                    TransactionMatchingSuggestion::belongs_to_transaction,
                    fn(Builder $q) => $q->where(Transaction::user_id, auth()->id())
                )
            )
            ->defaultSort(TransactionMatchingSuggestion::score, 'desc')
            ->columns([
                TextColumn::make(
                    TransactionMatchingSuggestion::belongs_to_transaction . '.' . Transaction::date
                )
                    ->label('Datum')
                    ->date()
                    ->sortable(),
                TextColumn::make(
                    TransactionMatchingSuggestion::belongs_to_transaction . '.' . Transaction::payer
                )
                    ->label('Auftraggeber')
                    ->searchable(),
                TextColumn::make(
                    TransactionMatchingSuggestion::belongs_to_transaction . '.' . Transaction::purpose
                )
                    ->label('Verwendungszweck')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make(
                    TransactionMatchingSuggestion::belongs_to_transaction . '.' . Transaction::amount
                )
                    ->label('Betrag')
                    ->numeric()
                    ->color(fn(TransactionMatchingSuggestion $record) => ((float)($record->transaction?->amount ?? 0)) >= 0 ? 'success' : 'danger'
                    ),
                TextColumn::make(
                    TransactionMatchingSuggestion::belongs_to_fixed_cost . '.' . FixedCost::name
                )
                    ->label('Vorgeschlagene Fixkosten')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make(TransactionMatchingSuggestion::score)
                    ->label('Score')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' / 100')
                    ->sortable(),
                TextColumn::make(TransactionMatchingSuggestion::status)
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => MatchingSuggestionStatusEnum::{$state}->label())
                    ->color(fn(string $state) => MatchingSuggestionStatusEnum::{$state}->color()),
            ])
            ->filters([
                SelectFilter::make(TransactionMatchingSuggestion::status)
                    ->label('Status')
                    ->options(MatchingSuggestionStatusEnum::options())
                    ->default(MatchingSuggestionStatusEnum::PENDING->name),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Akzeptieren')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn(TransactionMatchingSuggestion $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Vorschlag akzeptieren')
                    ->modalDescription('Die Transaktion wird mit den Fixkosten verknüpft. Alle anderen Vorschläge für diese Transaktion werden abgelehnt.')
                    ->action(function (TransactionMatchingSuggestion $record): void {
                        DB::transaction(function () use ($record): void {
                            // Transaktion verknüpfen
                            $record->transaction()->update([
                                Transaction::fixed_cost_id => $record->fixed_cost_id,
                            ]);

                            // Diesen Vorschlag akzeptieren
                            $record->update([
                                TransactionMatchingSuggestion::status => MatchingSuggestionStatusEnum::ACCEPTED->name,
                            ]);

                            // Alle anderen Vorschläge für dieselbe Transaktion ablehnen
                            TransactionMatchingSuggestion::query()
                                ->where(TransactionMatchingSuggestion::transaction_id, $record->transaction_id)
                                ->where(TransactionMatchingSuggestion::id, '!=', $record->id)
                                ->update([
                                    TransactionMatchingSuggestion::status => MatchingSuggestionStatusEnum::REJECTED->name,
                                ]);

                            app(FixedCostMatchingLearningService::class)
                                ->learnFromAcceptedSuggestion($record->fresh([
                                    TransactionMatchingSuggestion::belongs_to_transaction,
                                    TransactionMatchingSuggestion::belongs_to_fixed_cost,
                                ]));
                        });

                        Notification::make()
                            ->title('Transaktion erfolgreich verknüpft.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Ablehnen')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn(TransactionMatchingSuggestion $record) => $record->isPending())
                    ->action(function (TransactionMatchingSuggestion $record): void {
                        $record->update([
                            TransactionMatchingSuggestion::status => MatchingSuggestionStatusEnum::REJECTED->name,
                        ]);

                        app(FixedCostMatchingLearningService::class)
                            ->learnFromRejectedSuggestion($record->fresh([
                                TransactionMatchingSuggestion::belongs_to_transaction,
                                TransactionMatchingSuggestion::belongs_to_fixed_cost,
                            ]));

                        Notification::make()
                            ->title('Vorschlag abgelehnt.')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatchingSuggestions::route('/'),
        ];
    }
}

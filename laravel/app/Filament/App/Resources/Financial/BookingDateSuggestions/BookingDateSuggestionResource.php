<?php

namespace App\Filament\App\Resources\Financial\BookingDateSuggestions;

use App\Filament\App\Resources\Financial\BookingDateSuggestions\Pages\ListBookingDateSuggestions;
use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Menu\NavigationGroup;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostBookingDateSuggestion;
use App\Models\Financial\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BookingDateSuggestionResource extends Resource
{
    protected static ?string $model = FixedCostBookingDateSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::FIXED_COSTS;
    protected static ?int $navigationSort = 25;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.booking_date_suggestion.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.booking_date_suggestion.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.booking_date_suggestion.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FixedCostBookingDateSuggestion::query()
            ->where(FixedCostBookingDateSuggestion::status, MatchingSuggestionStatusEnum::PENDING->name)
            ->whereHas(
                FixedCostBookingDateSuggestion::belongs_to_fixed_cost,
                fn(Builder $q) => $q->where(FixedCost::user_id, auth()->id())
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
                ->with([FixedCostBookingDateSuggestion::belongs_to_fixed_cost])
                ->whereHas(
                    FixedCostBookingDateSuggestion::belongs_to_fixed_cost,
                    fn(Builder $q) => $q->where(FixedCost::user_id, auth()->id())
                )
            )
            ->defaultSort(FixedCostBookingDateSuggestion::deviation_days, 'desc')
            ->columns([
                TextColumn::make(
                    FixedCostBookingDateSuggestion::belongs_to_fixed_cost . '.' . FixedCost::name
                )
                    ->label('Fixkost')
                    ->searchable()
                    ->url(fn(FixedCostBookingDateSuggestion $record) => FixedCostResource::getViewUrl($record->fixed_cost_id)),
                TextColumn::make(
                    FixedCostBookingDateSuggestion::belongs_to_fixed_cost . '.' . FixedCost::amount
                )
                    ->label('Betrag')
                    ->numeric()
                    ->color(fn(FixedCostBookingDateSuggestion $record) => ((float)($record->fixedCost?->amount ?? 0)) >= 0 ? 'success' : 'danger'),
                TextColumn::make(
                    FixedCostBookingDateSuggestion::belongs_to_fixed_cost . '.' . FixedCost::interval
                )
                    ->label('Intervall')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state !== null
                        ? (FixedCostIntervalEnum::tryFrom($state)?->label() ?? $state)
                        : '-'),
                TextColumn::make(FixedCostBookingDateSuggestion::current_date)
                    ->label('Aktuell')
                    ->date()
                    ->placeholder('-'),
                TextColumn::make(FixedCostBookingDateSuggestion::suggested_date)
                    ->label('Vorschlag')
                    ->date()
                    ->sortable(),
                TextColumn::make(FixedCostBookingDateSuggestion::deviation_days)
                    ->label('Abweichung')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn(int $state) => sprintf('± %d Tage', $state))
                    ->sortable(),
                TextColumn::make(FixedCostBookingDateSuggestion::sample_count)
                    ->label('Transaktionen')
                    ->numeric(),
                TextColumn::make(FixedCostBookingDateSuggestion::analyzed_from)
                    ->label('Zeitraum')
                    ->formatStateUsing(fn(FixedCostBookingDateSuggestion $record) => sprintf(
                        '%s – %s',
                        $record->analyzed_from->format('d.m.Y'),
                        $record->analyzed_to->format('d.m.Y'),
                    ))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(FixedCostBookingDateSuggestion::status)
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => MatchingSuggestionStatusEnum::{$state}->label())
                    ->color(fn(string $state) => MatchingSuggestionStatusEnum::{$state}->color()),
            ])
            ->filters([
                SelectFilter::make(FixedCostBookingDateSuggestion::status)
                    ->label('Status')
                    ->options(MatchingSuggestionStatusEnum::options())
                    ->default(MatchingSuggestionStatusEnum::PENDING->name),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Akzeptieren')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn(FixedCostBookingDateSuggestion $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Buchungstermin übernehmen')
                    ->modalDescription('Die Fixkost wird auf das vorgeschlagene Datum aktualisiert. Alle anderen Vorschläge für diese Fixkost werden abgelehnt.')
                    ->action(function (FixedCostBookingDateSuggestion $record): void {
                        DB::transaction(function () use ($record): void {
                            $record->fixedCost()->update([
                                FixedCost::next_booking_date => $record->suggested_date,
                            ]);

                            $record->update([
                                FixedCostBookingDateSuggestion::status => MatchingSuggestionStatusEnum::ACCEPTED->name,
                            ]);

                            FixedCostBookingDateSuggestion::query()
                                ->where(FixedCostBookingDateSuggestion::fixed_cost_id, $record->fixed_cost_id)
                                ->where(FixedCostBookingDateSuggestion::id, '!=', $record->id)
                                ->where(FixedCostBookingDateSuggestion::status, MatchingSuggestionStatusEnum::PENDING->name)
                                ->update([
                                    FixedCostBookingDateSuggestion::status => MatchingSuggestionStatusEnum::REJECTED->name,
                                ]);
                        });

                        Notification::make()
                            ->title('Buchungstermin wurde übernommen.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Ablehnen')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn(FixedCostBookingDateSuggestion $record) => $record->isPending())
                    ->action(function (FixedCostBookingDateSuggestion $record): void {
                        $record->update([
                            FixedCostBookingDateSuggestion::status => MatchingSuggestionStatusEnum::REJECTED->name,
                        ]);

                        Notification::make()
                            ->title('Vorschlag abgelehnt.')
                            ->warning()
                            ->send();
                    }),

                Action::make('details')
                    ->label('Details')
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->color('gray')
                    ->modalHeading('Grundlage des Vorschlags')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->modalContent(fn(FixedCostBookingDateSuggestion $record) => view(
                        'filament.app.resources.financial.booking-date-suggestions.actions.details',
                        ['record' => $record, 'transactions' => static::sampleTransactions($record)],
                    )),
            ]);
    }

    /**
     * @return Collection<int, Transaction>
     */
    private static function sampleTransactions(FixedCostBookingDateSuggestion $record): Collection
    {
        return Transaction::query()
            ->where(Transaction::fixed_cost_id, $record->fixed_cost_id)
            ->whereDate(Transaction::date, '>=', $record->analyzed_from)
            ->whereDate(Transaction::date, '<=', $record->analyzed_to)
            ->orderByDesc(Transaction::date)
            ->get();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingDateSuggestions::route('/'),
        ];
    }
}

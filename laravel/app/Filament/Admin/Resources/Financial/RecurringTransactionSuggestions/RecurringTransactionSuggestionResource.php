<?php

namespace App\Filament\Admin\Resources\Financial\RecurringTransactionSuggestions;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\Admin\Resources\Financial\FixedCosts\Schemas\FixedCostForm;
use App\Filament\Admin\Resources\Financial\RecurringTransactionSuggestions\Pages\ListRecurringTransactionSuggestions;
use App\Menu\NavigationGroup;
use App\Models\Enums\FixedCostCategoryEnum;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\RecurringTransactionSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\RecurringTransactionSuggestion;
use App\Models\Financial\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RecurringTransactionSuggestionResource extends Resource
{
    protected static ?string $model = RecurringTransactionSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPathRoundedSquare;
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::FIXED_COSTS;
    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('admin.resource.recurring_transaction_suggestion.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resource.recurring_transaction_suggestion.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resource.recurring_transaction_suggestion.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = RecurringTransactionSuggestion::query()
            ->where(RecurringTransactionSuggestion::user_id, auth()->id())
            ->where(RecurringTransactionSuggestion::status, RecurringTransactionSuggestionStatusEnum::PENDING->name)
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
                ->where(RecurringTransactionSuggestion::user_id, auth()->id())
            )
            ->defaultSort(RecurringTransactionSuggestion::occurrence_count, 'desc')
            ->columns([
                TextColumn::make(RecurringTransactionSuggestion::name_hint)
                    ->label('Name-Vorschlag')
                    ->searchable(),
                TextColumn::make(RecurringTransactionSuggestion::payer)
                    ->label('Auftraggeber')
                    ->limit(35)
                    ->searchable(),
                TextColumn::make(RecurringTransactionSuggestion::purpose)
                    ->label('Verwendungszweck')
                    ->limit(45)
                    ->searchable(),
                TextColumn::make(RecurringTransactionSuggestion::amount)
                    ->label('Betrag')
                    ->formatStateUsing(fn(RecurringTransactionSuggestion $record): string => number_format((float)$record->amount, 2, ',', '.') . ' ' . ($record->amount_currency ?? ''))
                    ->color(fn(RecurringTransactionSuggestion $record) => ((float)$record->amount) >= 0 ? 'success' : 'danger'),
                TextColumn::make(RecurringTransactionSuggestion::occurrence_count)
                    ->label('Treffer')
                    ->sortable(),
                TextColumn::make(RecurringTransactionSuggestion::first_seen_at)
                    ->label('Erstmals')
                    ->date(),
                TextColumn::make(RecurringTransactionSuggestion::last_seen_at)
                    ->label('Zuletzt')
                    ->date(),
                TextColumn::make(RecurringTransactionSuggestion::status)
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => RecurringTransactionSuggestionStatusEnum::{$state}->label())
                    ->color(fn(string $state) => RecurringTransactionSuggestionStatusEnum::{$state}->color()),
            ])
            ->filters([
                SelectFilter::make(RecurringTransactionSuggestion::status)
                    ->label('Status')
                    ->options(RecurringTransactionSuggestionStatusEnum::options())
                    ->default(RecurringTransactionSuggestionStatusEnum::PENDING->name),
            ])
            ->recordActions([
                Action::make('create_fixed_cost')
                    ->label('Fixkosten erstellen')
                    ->icon(Heroicon::Plus)
                    ->color('success')
                    ->visible(fn(RecurringTransactionSuggestion $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalWidth(Width::FitContent)
                    ->modalHeading('Fixkosten aus Vorschlag erstellen')
                    ->modalDescription('Es werden Fixkosten erstellt und passende, unverknüpfte Transaktionen aus dem erkannten Zeitraum verknüpft.')
                    ->schema(FixedCostForm::getSchema())
                    ->fillForm(function (RecurringTransactionSuggestion $record) {
                        return [
                            FixedCost::name => $record->name_hint,
                            FixedCost::amount => $record->amount,
                            FixedCost::category => FixedCostCategoryEnum::default(),
                            FixedCost::interval => FixedCostIntervalEnum::default(),
                            FixedCost::ends_mode => FixedCostEndsModeEnum::default(),
                            FixedCost::next_booking_date => now()->addMonth()->startOfMonth(),
                        ];
                    })
                    ->action(function (RecurringTransactionSuggestion $record, array $data = []): void {
                        $fixedCost = DB::transaction(function () use ($record, $data): FixedCost {
                            $createdFixedCost = FixedCost::query()->create(array_merge($data, [
                                FixedCost::user_id => $record->user_id,
                            ]));

                            $query = Transaction::query()
                                ->where(Transaction::user_id, $record->user_id)
                                ->whereNull(Transaction::fixed_cost_id)
                                ->whereBetween(Transaction::date, [$record->first_seen_at, $record->last_seen_at])
                                ->where(Transaction::amount, (float)$record->amount);

                            if ($record->payer === null) {
                                $query->whereNull(Transaction::payer);
                            } else {
                                $query->where(Transaction::payer, $record->payer);
                            }

                            if ($record->purpose === null) {
                                $query->whereNull(Transaction::purpose);
                            } else {
                                $query->where(Transaction::purpose, $record->purpose);
                            }

                            $query->update([
                                Transaction::fixed_cost_id => $createdFixedCost->id,
                            ]);

                            $record->update([
                                RecurringTransactionSuggestion::status => RecurringTransactionSuggestionStatusEnum::ACCEPTED->name,
                            ]);

                            return $createdFixedCost;
                        });

                        Notification::make()
                            ->title('Fixkosten erstellt und passende Transaktionen verknüpft.')
                            ->success()
                            ->send();

                        redirect(FixedCostResource::getViewUrl($fixedCost->id));
                    }),
                Action::make('dismiss')
                    ->label('Ausblenden')
                    ->icon(Heroicon::XMark)
                    ->color('gray')
                    ->visible(fn(RecurringTransactionSuggestion $record) => $record->isPending())
                    ->action(function (RecurringTransactionSuggestion $record): void {
                        $record->update([
                            RecurringTransactionSuggestion::status => RecurringTransactionSuggestionStatusEnum::DISMISSED->name,
                        ]);

                        Notification::make()
                            ->title('Vorschlag ausgeblendet.')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecurringTransactionSuggestions::route('/'),
        ];
    }
}


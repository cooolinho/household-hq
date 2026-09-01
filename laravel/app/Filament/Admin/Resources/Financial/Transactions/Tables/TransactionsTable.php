<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Tables;

use App\Filament\Admin\Resources\Financial\Transactions\Actions\CategorizeTransactionAction;
use App\Filament\Admin\Resources\Financial\Transactions\Actions\CreateFixedCostAction;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Transaction::date)
                    ->label('Buchung')
                    ->date()
                    ->sortable(),
                TextColumn::make(Transaction::payer)
                    ->label('Auftraggeber')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make(Transaction::purpose)
                    ->label('Verwendungszweck')
                    ->searchable()
                    ->limit(70),
                TextColumn::make(Transaction::amount)
                    ->label('Betrag')
                    ->formatStateUsing(fn(Transaction $record): string => number_format($record->amount, 2, ',', '.') . ' ' . $record->amount_currency)
                    ->sortable()
                    ->alignEnd()
                    ->color(fn(Transaction $record) => $record->amount >= 0 ? 'success' : 'danger'),
                // Fixkosten-Verknüpfung
                TextColumn::make(Transaction::belongs_to_fixed_cost . '.' . FixedCost::name)
                    ->label('Fixkosten')
                    ->placeholder('—')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                TextColumn::make(Transaction::belongs_to_many_transaction_categories . '.' . TransactionCategory::name)
                    ->label('Kategorien')
                    ->placeholder('—')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make(Transaction::value_date)
                    ->label('Wertstellung')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::description)
                    ->label('Beschreibung')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::balance)
                    ->label('Kontostand')
                    ->formatStateUsing(fn(Transaction $record): string => number_format($record->balance, 2, ',', '.') . ' ' . $record->balance_currency)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(Transaction::date, 'desc')
            ->filters([
                // Typ: Einnahme / Ausgabe
                SelectFilter::make('amount_type')
                    ->label('Typ')
                    ->options([
                        'income' => 'Einnahmen',
                        'expense' => 'Ausgaben',
                    ])
                    ->query(fn(Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'income' => $query->where(Transaction::amount, '>', 0),
                        'expense' => $query->where(Transaction::amount, '<', 0),
                        default => $query,
                    }),

                // Kategorie
                SelectFilter::make(Transaction::belongs_to_many_transaction_categories)
                    ->label('Kategorie')
                    ->options(fn() => TransactionCategory::query()
                        ->where(TransactionCategory::active, true)
                        ->where(TransactionCategory::user_id, auth()->id())
                        ->with(TransactionCategory::belongs_to_parent)
                        ->get()
                        ->mapWithKeys(fn(TransactionCategory $category) => [
                            $category->id => $category->getFullNameAttribute(),
                        ])
                        ->toArray())
                    ->searchable()
                    ->query(fn(Builder $query, array $data): Builder => isset($data['value'])
                        ? $query->whereHas(
                            Transaction::belongs_to_many_transaction_categories,
                            fn(Builder $q) => $q->where('transaction_category_id', $data['value'])
                        )
                        : $query),

                // Ohne Kategorie
                Filter::make('uncategorized')
                    ->label('Ohne Kategorie')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->whereDoesntHave(
                        Transaction::belongs_to_many_transaction_categories
                    )),

                // Fixkosten-Zuordnung
                SelectFilter::make('fixed_cost_status')
                    ->label('Fixkosten')
                    ->options([
                        'assigned' => 'Zugeordnet',
                        'unassigned' => 'Nicht zugeordnet',
                    ])
                    ->query(fn(Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'assigned' => $query->whereNotNull(Transaction::fixed_cost_id),
                        'unassigned' => $query->whereNull(Transaction::fixed_cost_id),
                        default => $query,
                    }),

                // Datumszeitraum
                Filter::make('date_range')
                    ->label('Zeitraum')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('Von')
                            ->native(false),
                        DatePicker::make('date_until')
                            ->label('Bis')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn(Builder $q, string $date) => $q->whereDate(Transaction::date, '>=', $date)
                            )
                            ->when(
                                $data['date_until'] ?? null,
                                fn(Builder $q, string $date) => $q->whereDate(Transaction::date, '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'Von: ' . $data['date_from'];
                        }
                        if ($data['date_until'] ?? null) {
                            $indicators[] = 'Bis: ' . $data['date_until'];
                        }
                        return $indicators;
                    }),

                // Betragsbereich
                Filter::make('amount_range')
                    ->label('Betrag')
                    ->form([
                        TextInput::make('amount_from')
                            ->label('Min. Betrag')
                            ->numeric()
                            ->placeholder('z.B. -50'),
                        TextInput::make('amount_until')
                            ->label('Max. Betrag')
                            ->numeric()
                            ->placeholder('z.B. 100'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                isset($data['amount_from']) && $data['amount_from'] !== '',
                                fn(Builder $q) => $q->where(Transaction::amount, '>=', (float)$data['amount_from'])
                            )
                            ->when(
                                isset($data['amount_until']) && $data['amount_until'] !== '',
                                fn(Builder $q) => $q->where(Transaction::amount, '<=', (float)$data['amount_until'])
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['amount_from']) && $data['amount_from'] !== '') {
                            $indicators[] = 'Min: ' . number_format((float)$data['amount_from'], 2, ',', '.') . ' €';
                        }
                        if (isset($data['amount_until']) && $data['amount_until'] !== '') {
                            $indicators[] = 'Max: ' . number_format((float)$data['amount_until'], 2, ',', '.') . ' €';
                        }
                        return $indicators;
                    }),
            ])
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->label('Details')
                    ->modalHeading('Transaktionsdetails')
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->extraModalFooterActions([
                        CreateFixedCostAction::make(),
                        CategorizeTransactionAction::make(),
                    ]),
                ActionGroup::make([
                    EditAction::make(),
                    CreateFixedCostAction::make(),
                    CategorizeTransactionAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

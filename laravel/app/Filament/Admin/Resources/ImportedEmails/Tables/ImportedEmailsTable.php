<?php

namespace App\Filament\Admin\Resources\ImportedEmails\Tables;

use App\Models\ImportedEmail;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ImportedEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(ImportedEmail::received_at, 'desc')
            ->columns([
                TextColumn::make(ImportedEmail::subject)
                    ->label(__('admin.resource.imported_email.fields.subject'))
                    ->searchable()
                    ->placeholder('-')
                    ->limit(60),
                TextColumn::make(ImportedEmail::from_email)
                    ->label(__('admin.resource.imported_email.fields.from_email'))
                    ->searchable()
                    ->placeholder('-')
                    ->limit(40),
                TextColumn::make(ImportedEmail::belongs_to_imap_account . '.' . \App\Models\ImapAccount::name)
                    ->label(__('admin.resource.imported_email.fields.imap_account'))
                    ->sortable(),
                TextColumn::make(ImportedEmail::mailbox_folder)
                    ->label(__('admin.resource.imported_email.fields.mailbox_folder'))
                    ->badge(),
                TextColumn::make(ImportedEmail::has_many_attachments . '_count')
                    ->label(__('admin.resource.imported_email.fields.attachments_count'))
                    ->numeric(),
                TextColumn::make(ImportedEmail::warning_count)
                    ->label(__('admin.resource.imported_email.fields.warning_count'))
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make(ImportedEmail::warning_summary)
                    ->label(__('admin.resource.imported_email.fields.warning_summary'))
                    ->placeholder('-')
                    ->limit(80)
                    ->toggleable(),
                IconColumn::make(ImportedEmail::marked_as_read)
                    ->label(__('admin.resource.imported_email.fields.marked_as_read'))
                    ->boolean(),
                IconColumn::make(ImportedEmail::moved_to_processed)
                    ->label(__('admin.resource.imported_email.fields.moved_to_processed'))
                    ->boolean(),
                TextColumn::make(ImportedEmail::received_at)
                    ->label(__('admin.resource.imported_email.fields.received_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make(ImportedEmail::processed_at)
                    ->label(__('admin.resource.imported_email.fields.processed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make(ImportedEmail::moved_to_processed)
                    ->label(__('admin.resource.imported_email.filters.moved_to_processed')),
                TernaryFilter::make('has_warnings')
                    ->label(__('admin.resource.imported_email.filters.has_warnings'))
                    ->queries(
                        true: fn($query) => $query->where(ImportedEmail::warning_count, '>', 0),
                        false: fn($query) => $query->where(ImportedEmail::warning_count, '=', 0),
                        blank: fn($query) => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ])->button(),
            ]);
    }
}


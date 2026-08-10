<?php

namespace App\Filament\Admin\Resources\ImportedEmails\Schemas;

use App\Models\ImportedEmail;
use App\Models\ImportedEmailAttachment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ImportedEmailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(ImportedEmail::subject)
                    ->label(__('admin.resource.imported_email.fields.subject'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make(ImportedEmail::from_name)
                    ->label(__('admin.resource.imported_email.fields.from_name'))
                    ->placeholder('-'),
                TextEntry::make(ImportedEmail::from_email)
                    ->label(__('admin.resource.imported_email.fields.from_email'))
                    ->placeholder('-'),
                TextEntry::make(ImportedEmail::message_id)
                    ->label(__('admin.resource.imported_email.fields.message_id'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make(ImportedEmail::uid)
                    ->label(__('admin.resource.imported_email.fields.uid'))
                    ->placeholder('-'),
                TextEntry::make(ImportedEmail::mailbox_folder)
                    ->label(__('admin.resource.imported_email.fields.mailbox_folder')),
                TextEntry::make(ImportedEmail::warning_count)
                    ->label(__('admin.resource.imported_email.fields.warning_count'))
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'warning' : 'gray'),
                TextEntry::make(ImportedEmail::warning_summary)
                    ->label(__('admin.resource.imported_email.fields.warning_summary'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('attachments_summary')
                    ->label(__('admin.resource.imported_email.fields.attachments'))
                    ->state(function (ImportedEmail $record): string {
                        $items = $record->attachments
                            ->sortBy(ImportedEmailAttachment::filename)
                            ->map(function (ImportedEmailAttachment $attachment): string {
                                $filename = $attachment->{ImportedEmailAttachment::filename} ?: '-';
                                $status = strtolower((string)$attachment->{ImportedEmailAttachment::status});
                                $reason = $attachment->{ImportedEmailAttachment::skip_reason}
                                    ?: $attachment->{ImportedEmailAttachment::error_message};

                                if (!empty($reason)) {
                                    return sprintf('%s (%s: %s)', $filename, $status, $reason);
                                }

                                return sprintf('%s (%s)', $filename, $status);
                            })
                            ->values();

                        if ($items->isEmpty()) {
                            return '-';
                        }

                        return $items->implode("\n");
                    })
                    ->html()
                    ->formatStateUsing(fn(string $state): string => nl2br(e($state)))
                    ->columnSpanFull(),
                TextEntry::make(ImportedEmail::marked_as_read)
                    ->label(__('admin.resource.imported_email.fields.marked_as_read'))
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? __('Ja') : __('Nein'))
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray'),
                TextEntry::make(ImportedEmail::moved_to_processed)
                    ->label(__('admin.resource.imported_email.fields.moved_to_processed'))
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? __('Ja') : __('Nein'))
                    ->color(fn(bool $state): string => $state ? 'success' : 'danger'),
                TextEntry::make(ImportedEmail::received_at)
                    ->label(__('admin.resource.imported_email.fields.received_at'))
                    ->dateTime(),
                TextEntry::make(ImportedEmail::processed_at)
                    ->label(__('admin.resource.imported_email.fields.processed_at'))
                    ->dateTime(),
            ]);
    }
}


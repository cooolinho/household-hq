<?php

namespace App\Filament\App\Resources\ApplicationLogs\Schemas;

use App\Models\ApplicationLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ApplicationLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(ApplicationLog::occurred_at)
                    ->label(__('admin.resource.application_log.fields.occurred_at'))
                    ->dateTime('d.m.Y H:i:s'),
                TextEntry::make(ApplicationLog::event)
                    ->label(__('admin.resource.application_log.fields.event'))
                    ->badge(),
                TextEntry::make(ApplicationLog::level)
                    ->label(__('admin.resource.application_log.fields.level'))
                    ->badge(),
                TextEntry::make(ApplicationLog::channel)
                    ->label(__('admin.resource.application_log.fields.channel'))
                    ->badge(),
                TextEntry::make(ApplicationLog::message)
                    ->label(__('admin.resource.application_log.fields.message'))
                    ->columnSpanFull(),
            ]);
    }
}


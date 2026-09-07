<?php

namespace App\Filament\App\Resources\ApplicationLogs\Pages;

use App\Filament\App\Resources\ApplicationLogs\ApplicationLogResource;
use App\Models\ApplicationLog;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewApplicationLog extends ViewRecord
{
    protected static string $resource = ApplicationLogResource::class;

    protected string $view = 'filament.app.resources.application-logs.pages.view-application-log';

    public function getTitle(): string|Htmlable
    {
        return __('admin.resource.application_log.model_label');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        /** @var ApplicationLog $record */
        $record = $this->record;

        $prettyContext = $record->{ApplicationLog::context} !== null
            ? json_encode($record->{ApplicationLog::context}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        return [
            'record' => $record,
            'prettyContext' => $prettyContext,
        ];
    }
}


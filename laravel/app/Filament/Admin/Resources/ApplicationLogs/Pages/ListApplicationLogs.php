<?php

namespace App\Filament\Admin\Resources\ApplicationLogs\Pages;

use App\Filament\Admin\Resources\ApplicationLogs\ApplicationLogResource;
use App\Models\ApplicationLog;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListApplicationLogs extends ListRecords
{
    protected static string $resource = ApplicationLogResource::class;
    protected string $view = 'filament.admin.resources.application-logs.pages.list-application-logs';

    public string $search = '';

    public string $eventFilter = 'all';

    public string $levelFilter = 'all';

    public ?string $fromDate = null;

    public ?string $untilDate = null;

    public function getApplicationLogsProperty(): LengthAwarePaginator
    {
        $search = trim($this->search);

        $query = ApplicationLog::query()
            ->with(ApplicationLog::belongs_to_user)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $needle = '%' . $search . '%';

                $query->where(function (Builder $query) use ($needle): void {
                    $query
                        ->where(ApplicationLog::event, 'like', $needle)
                        ->orWhere(ApplicationLog::message, 'like', $needle)
                        ->orWhere(ApplicationLog::channel, 'like', $needle)
                        ->orWhere(ApplicationLog::level, 'like', $needle)
                        ->orWhereHas(ApplicationLog::belongs_to_user, function (Builder $query) use ($needle): void {
                            $query->where('email', 'like', $needle);
                        });
                });
            })
            ->when($this->eventFilter !== 'all', fn(Builder $query) => $query->where(ApplicationLog::event, $this->eventFilter))
            ->when($this->levelFilter !== 'all', fn(Builder $query) => $query->where(ApplicationLog::level, $this->levelFilter))
            ->when(filled($this->fromDate), fn(Builder $query) => $query->whereDate(ApplicationLog::occurred_at, '>=', $this->fromDate))
            ->when(filled($this->untilDate), fn(Builder $query) => $query->whereDate(ApplicationLog::occurred_at, '<=', $this->untilDate))
            ->orderByDesc(ApplicationLog::occurred_at);

        return $query->paginate(20);
    }

    public function getEventOptionsProperty(): array
    {
        return ApplicationLog::query()
            ->whereNotNull(ApplicationLog::event)
            ->distinct()
            ->orderBy(ApplicationLog::event)
            ->pluck(ApplicationLog::event, ApplicationLog::event)
            ->toArray();
    }

    public function getLevelOptionsProperty(): array
    {
        return [
            'debug' => 'debug',
            'info' => 'info',
            'notice' => 'notice',
            'warning' => 'warning',
            'error' => 'error',
            'critical' => 'critical',
            'alert' => 'alert',
            'emergency' => 'emergency',
        ];
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'eventFilter', 'levelFilter', 'fromDate', 'untilDate']);
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
    }

    public function updatingUntilDate(): void
    {
        $this->resetPage();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}


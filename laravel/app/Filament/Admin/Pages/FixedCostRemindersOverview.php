<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Menu\NavigationGroup;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostReminder;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;

class FixedCostRemindersOverview extends Page
{
    protected static ?string $title = 'Reminder-Übersicht';
    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::FIXED_COSTS;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-bell';
    protected static ?int $navigationSort = 15;
    protected static bool $shouldRegisterNavigation = true;
    public array $summary = [];
    public array $reminders = [];
    public string $search = '';
    public string $statusFilter = 'all';
    public string $channelFilter = 'all';
    public string $sortColumn = 'next_reminder_timestamp';
    public string $sortDirection = 'asc';
    protected string $view = 'filament.admin.pages.fixed-cost-reminders-overview';

    public static function getNavigationLabel(): string
    {
        return 'Reminder-Übersicht';
    }

    public function mount(): void
    {
        $this->reloadData();
    }

    private function reloadData(): void
    {
        $fixedCostReminders = FixedCostReminder::query()
            ->with(FixedCostReminder::belongs_to_fixed_cost)
            ->whereHas(FixedCostReminder::belongs_to_fixed_cost, function ($query): void {
                $query->where(FixedCost::user_id, auth()->id());
            })
            ->get();

        $today = now()->startOfDay();
        $nextWeek = $today->copy()->addDays(7);

        $allRows = $fixedCostReminders
            ->map(function (FixedCostReminder $reminder) use ($today, $nextWeek): array {
                $fixedCost = $reminder->fixedCost;
                $bookingDate = $fixedCost?->{FixedCost::next_booking_date};
                $reminderDate = FixedCostReminder::calculateReminderDate($bookingDate, (int)$reminder->{FixedCostReminder::days_before});

                return [
                    'id' => $reminder->id,
                    'fixed_cost_id' => $fixedCost?->getKey(),
                    'fixed_cost_name' => (string)($fixedCost?->{FixedCost::name} ?? '-'),
                    'fixed_cost_url' => $fixedCost ? FixedCostResource::getViewUrl((int)$fixedCost->getKey()) : null,
                    'next_booking_date' => $bookingDate?->format('d.m.Y') ?? '-',
                    'next_booking_timestamp' => $bookingDate?->startOfDay()?->getTimestamp() ?? PHP_INT_MAX,
                    'next_reminder_date' => $reminderDate?->format('d.m.Y') ?? '-',
                    'next_reminder_timestamp' => $reminderDate?->startOfDay()?->getTimestamp() ?? PHP_INT_MAX,
                    'days_before' => (int)$reminder->{FixedCostReminder::days_before},
                    'lead_time_label' => FixedCostReminder::leadTimeLabel((int)$reminder->{FixedCostReminder::days_before}),
                    'send_mail' => (bool)$reminder->{FixedCostReminder::send_mail},
                    'send_notification' => (bool)$reminder->{FixedCostReminder::send_notification},
                    'channels_label' => FixedCostReminder::channelsLabel((bool)$reminder->{FixedCostReminder::send_mail}, (bool)$reminder->{FixedCostReminder::send_notification}),
                    'enabled' => (bool)$reminder->{FixedCostReminder::enabled},
                    'last_sent_booking_date' => $reminder->{FixedCostReminder::last_sent_booking_date}?->format('d.m.Y') ?? '-',
                    'last_sent_booking_timestamp' => $reminder->{FixedCostReminder::last_sent_booking_date}?->startOfDay()?->getTimestamp() ?? PHP_INT_MAX,
                    'is_due_today' => $reminderDate !== null && $reminderDate->isSameDay($today),
                    'is_due_soon' => $reminderDate !== null && $reminderDate->greaterThan($today) && $reminderDate->lessThanOrEqualTo($nextWeek),
                ];
            });

        $this->summary = [
            'total' => $allRows->count(),
            'active' => $allRows->where('enabled', true)->count(),
            'due_today' => $allRows->where('is_due_today', true)->count(),
            'due_next_week' => $allRows->where('is_due_soon', true)->count(),
        ];

        $filteredRows = $this->applyFilters($allRows);
        $sortedRows = $this->applySort($filteredRows);

        $this->reminders = $sortedRows->values()->all();
        $this->summary['visible'] = $sortedRows->count();
    }

    private function applyFilters(Collection $rows): Collection
    {
        $filtered = $rows;

        if ($this->search !== '') {
            $needle = mb_strtolower(trim($this->search));
            $filtered = $filtered->filter(function (array $row) use ($needle): bool {
                return str_contains(mb_strtolower($row['fixed_cost_name']), $needle);
            });
        }

        $filtered = match ($this->statusFilter) {
            'active' => $filtered->where('enabled', true),
            'inactive' => $filtered->where('enabled', false),
            default => $filtered,
        };

        return match ($this->channelFilter) {
            'mail' => $filtered->where('send_mail', true),
            'notification' => $filtered->where('send_notification', true),
            'both' => $filtered->filter(fn(array $row): bool => $row['send_mail'] && $row['send_notification']),
            default => $filtered,
        };
    }

    private function applySort(Collection $rows): Collection
    {
        $sortColumn = $this->sortColumn;

        $sorted = $rows->sortBy(function (array $row) use ($sortColumn) {
            return $row[$sortColumn] ?? null;
        });

        return $this->sortDirection === 'desc' ? $sorted->reverse()->values() : $sorted->values();
    }

    public function updatedSearch(): void
    {
        $this->reloadData();
    }

    public function updatedStatusFilter(): void
    {
        $this->reloadData();
    }

    public function updatedChannelFilter(): void
    {
        $this->reloadData();
    }

    public function sortByColumn(string $column): void
    {
        $allowedColumns = [
            'fixed_cost_name',
            'next_booking_timestamp',
            'next_reminder_timestamp',
            'days_before',
            'channels_label',
            'enabled',
            'last_sent_booking_timestamp',
        ];

        if (!in_array($column, $allowedColumns, true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->reloadData();
    }

    public function sortIndicator(string $column): string
    {
        if ($this->sortColumn !== $column) {
            return '';
        }

        return $this->sortDirection === 'asc' ? '↑' : '↓';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createReminder')
                ->label('Reminder schnell anlegen')
                ->icon('heroicon-o-plus')
                ->modalHeading('Reminder anlegen')
                ->schema([
                    Select::make(FixedCostReminder::fixed_cost_id)
                        ->label('Fixkosten')
                        ->options($this->fixedCostOptions())
                        ->searchable()
                        ->preload()
                        ->helperText(function (Get $get) {
                            $fixedCost = FixedCost::query()->find($get(FixedCostReminder::fixed_cost_id));
                            $nextBookingDate = $fixedCost?->{FixedCost::next_booking_date};
                            return FixedCostReminder::reminderInfoText($nextBookingDate, (int)$get(FixedCostReminder::days_before));
                        })
                        ->reactive()
                        ->required(),
                    Select::make(FixedCostReminder::days_before)
                        ->label('Vorlauf')
                        ->options(FixedCostReminder::leadTimeOptions())
                        ->default(1)
                        ->required(),
                    Toggle::make(FixedCostReminder::send_mail)
                        ->label('Per E-Mail senden')
                        ->default(true),
                    Toggle::make(FixedCostReminder::send_notification)
                        ->label('Per Benachrichtigung senden')
                        ->default(false),
                    Toggle::make(FixedCostReminder::enabled)
                        ->label('Aktiv')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    FixedCostReminder::query()->updateOrCreate(
                        [
                            FixedCostReminder::fixed_cost_id => (int)$data[FixedCostReminder::fixed_cost_id],
                            FixedCostReminder::days_before => (int)$data[FixedCostReminder::days_before],
                        ],
                        [
                            FixedCostReminder::send_mail => (bool)$data[FixedCostReminder::send_mail],
                            FixedCostReminder::send_notification => (bool)$data[FixedCostReminder::send_notification],
                            FixedCostReminder::enabled => (bool)$data[FixedCostReminder::enabled],
                        ],
                    );

                    $this->reloadData();

                    Notification::make()
                        ->success()
                        ->title('Reminder wurde gespeichert.')
                        ->send();
                }),
        ];
    }

    private function fixedCostOptions(): array
    {
        return FixedCost::query()
            ->where(FixedCost::user_id, auth()->id())
            ->orderBy(FixedCost::name)
            ->pluck(FixedCost::name, FixedCost::id)
            ->toArray();
    }
}


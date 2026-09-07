<?php

namespace App\Filament\App\Resources\ApplicationLogs\Tables;

use App\Models\ApplicationLog;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApplicationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(ApplicationLog::occurred_at, 'desc')
            ->columns([
                TextColumn::make(ApplicationLog::occurred_at)
                    ->label(__('admin.resource.application_log.fields.occurred_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make(ApplicationLog::event)
                    ->label(__('admin.resource.application_log.fields.event'))
                    ->searchable()
                    ->badge(),
                TextColumn::make(ApplicationLog::level)
                    ->label(__('admin.resource.application_log.fields.level'))
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'error', 'critical', 'alert', 'emergency' => 'danger',
                        'warning' => 'warning',
                        'notice' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make(ApplicationLog::message)
                    ->label(__('admin.resource.application_log.fields.message'))
                    ->searchable()
                    ->limit(100),
                TextColumn::make(ApplicationLog::channel)
                    ->label(__('admin.resource.application_log.fields.channel'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make(ApplicationLog::event)
                    ->label(__('admin.resource.application_log.filters.event'))
                    ->options(fn() => ApplicationLog::query()
                        ->whereNotNull(ApplicationLog::event)
                        ->distinct()
                        ->orderBy(ApplicationLog::event)
                        ->pluck(ApplicationLog::event, ApplicationLog::event)
                        ->toArray())
                    ->searchable(),
                SelectFilter::make(ApplicationLog::level)
                    ->label(__('admin.resource.application_log.filters.level'))
                    ->options([
                        'debug' => 'debug',
                        'info' => 'info',
                        'notice' => 'notice',
                        'warning' => 'warning',
                        'error' => 'error',
                        'critical' => 'critical',
                        'alert' => 'alert',
                        'emergency' => 'emergency',
                    ]),
                Filter::make('occurred_between')
                    ->label(__('admin.resource.application_log.filters.occurred_between'))
                    ->schema([
                        DatePicker::make('from')->label(__('admin.resource.application_log.filters.from')),
                        DatePicker::make('until')->label(__('admin.resource.application_log.filters.until')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                !empty($data['from']),
                                fn($builder) => $builder->whereDate(ApplicationLog::occurred_at, '>=', $data['from'])
                            )
                            ->when(
                                !empty($data['until']),
                                fn($builder) => $builder->whereDate(ApplicationLog::occurred_at, '<=', $data['until'])
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ])->button(),
            ]);
    }
}


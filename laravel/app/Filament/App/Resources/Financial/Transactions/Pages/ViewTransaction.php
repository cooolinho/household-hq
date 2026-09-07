<?php

namespace App\Filament\App\Resources\Financial\Transactions\Pages;

use App\Filament\App\Resources\Financial\Transactions\TransactionResource;
use App\Filament\App\Widgets\CommentsWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            CommentsWidget::class,
        ];
    }
}

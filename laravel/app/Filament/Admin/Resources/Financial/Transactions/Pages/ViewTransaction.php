<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Pages;

use App\Filament\Admin\Resources\Financial\Transactions\TransactionResource;
use App\Filament\Admin\Widgets\CommentsWidget;
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

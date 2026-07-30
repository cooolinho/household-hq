<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Pages;

use App\Filament\Admin\Resources\Financial\Transactions\TransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}

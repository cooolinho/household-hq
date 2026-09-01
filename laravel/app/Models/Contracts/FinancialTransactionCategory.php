<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Model;

class FinancialTransactionCategory
{

    public const string PIVOT_TABLE = 'financial_transaction_transaction_category';
    public const string TRANSACTION_ID = 'transaction_id';
    public const string CATEGORY_ID = 'transaction_category_id';
    public const string CREATED_AT = Model::CREATED_AT;
}

<?php

use App\Models\Financial\TransactionCategoryRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(TransactionCategoryRule::TABLE, function (Blueprint $table) {
            $table->string(TransactionCategoryRule::type)
                ->default(TransactionCategoryRule::TYPE_INCLUDE)
                ->after(TransactionCategoryRule::key);
        });
    }

    public function down(): void
    {
        Schema::table(TransactionCategoryRule::TABLE, function (Blueprint $table) {
            $table->dropColumn(TransactionCategoryRule::type);
        });
    }
};

<?php

use App\Models\Financial\Budget;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(Budget::TABLE, function (Blueprint $table) {
            $table->boolean(Budget::include_in_balance)
                ->default(true)
                ->after(Budget::period);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(Budget::TABLE, function (Blueprint $table) {
            $table->dropColumn(Budget::include_in_balance);
        });
    }
};

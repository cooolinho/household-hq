<?php

use App\Models\DashboardWidgetPreference;
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
        Schema::table(DashboardWidgetPreference::TABLE, function (Blueprint $table) {
            $table->boolean(DashboardWidgetPreference::include_budgets_in_balance)
                ->default(true)
                ->after(DashboardWidgetPreference::balance_mode);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(DashboardWidgetPreference::TABLE, function (Blueprint $table) {
            $table->dropColumn(DashboardWidgetPreference::include_budgets_in_balance);
        });
    }
};

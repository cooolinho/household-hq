<?php

use App\Models\DashboardWidgetPreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(DashboardWidgetPreference::TABLE, function (Blueprint $table) {
            $table->unsignedTinyInteger(DashboardWidgetPreference::period_start_day)
                ->default(1)
                ->after(DashboardWidgetPreference::include_budgets_in_balance);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(DashboardWidgetPreference::TABLE, function (Blueprint $table) {
            $table->dropColumn(DashboardWidgetPreference::period_start_day);
        });
    }
};

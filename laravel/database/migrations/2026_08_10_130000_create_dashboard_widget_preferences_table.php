<?php

use App\Models\DashboardWidgetPreference;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(DashboardWidgetPreference::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, DashboardWidgetPreference::user_id)
                ->unique()
                ->constrained(User::TABLE)
                ->cascadeOnDelete();

            $table->boolean(DashboardWidgetPreference::show_monthly_balance_stats)->default(true);
            $table->boolean(DashboardWidgetPreference::show_monthly_balance_chart)->default(true);
            $table->boolean(DashboardWidgetPreference::show_upcoming_transactions_table)->default(true);
            $table->boolean(DashboardWidgetPreference::show_portfolio_overview)->default(true);

            $table->string(DashboardWidgetPreference::balance_mode)->default(DashboardWidgetPreference::BALANCE_MODE_BOTH);
            $table->string(DashboardWidgetPreference::currency, 8)->default('EUR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(DashboardWidgetPreference::TABLE);
    }
};


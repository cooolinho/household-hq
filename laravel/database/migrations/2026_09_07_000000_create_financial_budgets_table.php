<?php

use App\Models\Enums\BudgetIconEnum;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Financial\Budget;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(Budget::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, Budget::user_id)
                ->constrained(User::TABLE)
                ->cascadeOnDelete();
            $table->string(Budget::name);
            $table->text(Budget::description)->nullable();
            $table->enum(Budget::icon, BudgetIconEnum::allNames())
                ->default(BudgetIconEnum::default());
            $table->decimal(Budget::amount, 15, 2);
            $table->string(Budget::currency, 3)->default(Budget::DEFAULT_CURRENCY);
            $table->enum(Budget::period, BudgetPeriodEnum::allNames())
                ->default(BudgetPeriodEnum::default());
            $table->boolean(Budget::include_subcategories)->default(true);
            $table->unsignedSmallInteger(Budget::warning_threshold)
                ->default(Budget::DEFAULT_WARNING_THRESHOLD);
            $table->unsignedSmallInteger(Budget::critical_threshold)
                ->default(Budget::DEFAULT_CRITICAL_THRESHOLD);
            $table->boolean(Budget::send_mail)->default(false);
            $table->boolean(Budget::send_notification)->default(true);
            $table->boolean(Budget::active)->default(true);
            $table->unsignedInteger(Budget::sort)->default(0);
            $table->string(Budget::last_notified_level, 16)->nullable();
            $table->date(Budget::last_notified_period_start)->nullable();
            $table->timestamps();

            $table->index([
                Budget::user_id,
                Budget::active,
                Budget::sort,
            ], 'fb_user_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Budget::TABLE);
    }
};

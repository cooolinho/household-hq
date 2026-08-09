<?php

use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostReminder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(FixedCostReminder::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FixedCost::class, FixedCostReminder::fixed_cost_id)
                ->constrained(FixedCost::TABLE)
                ->cascadeOnDelete();
            $table->unsignedSmallInteger(FixedCostReminder::days_before);
            $table->boolean(FixedCostReminder::send_mail)->default(true);
            $table->boolean(FixedCostReminder::send_notification)->default(false);
            $table->boolean(FixedCostReminder::enabled)->default(true);
            $table->date(FixedCostReminder::last_sent_booking_date)->nullable();

            $table->timestamps();

            $table->unique([
                FixedCostReminder::fixed_cost_id,
                FixedCostReminder::days_before,
            ], 'fixed_cost_reminders_fixed_cost_id_days_before_unique');
            $table->index([
                FixedCostReminder::fixed_cost_id,
                FixedCostReminder::enabled,
            ], 'fixed_cost_reminders_fixed_cost_id_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(FixedCostReminder::TABLE);
    }
};


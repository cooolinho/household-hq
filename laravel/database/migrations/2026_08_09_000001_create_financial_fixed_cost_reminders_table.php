<?php

use App\Models\Financial\FixedCost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Hinweis: Das Model FixedCostReminder wurde durch das zentrale Reminder-System ersetzt
// (siehe reminders/reminder_schedules). Diese historische Migration referenziert die
// Tabelle/Spalten deshalb als Literale statt über das (entfernte) Model, die Tabelle selbst
// wird per Folgemigration wieder gedroppt.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_fixed_cost_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FixedCost::class, 'fixed_cost_id')
                ->constrained(FixedCost::TABLE)
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->boolean('send_mail')->default(true);
            $table->boolean('send_notification')->default(false);
            $table->boolean('enabled')->default(true);
            $table->date('last_sent_booking_date')->nullable();

            $table->timestamps();

            $table->unique([
                'fixed_cost_id',
                'days_before',
            ], 'fixed_cost_reminders_fixed_cost_id_days_before_unique');
            $table->index([
                'fixed_cost_id',
                'enabled',
            ], 'fixed_cost_reminders_fixed_cost_id_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_fixed_cost_reminders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('financial_fixed_cost_reminders');
    }

    public function down(): void
    {
        // Das Altsystem wurde durch das zentrale Reminder-System ersetzt und wird nicht wiederhergestellt.
    }
};

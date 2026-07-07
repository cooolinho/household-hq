<?php

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(ReadingEntry::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MeasurementDevice::class)
                ->constrained()
                ->onDelete('cascade');
            $table->string(ReadingEntry::reading_value);
            $table->dateTime(ReadingEntry::reading_date);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ReadingEntry::TABLE);
    }
};

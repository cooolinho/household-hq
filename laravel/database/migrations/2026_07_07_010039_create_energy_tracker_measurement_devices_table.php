<?php

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\Enums\EnergyTrackerCountingMethodEnum;
use App\Models\Enums\EnergyTrackerCountingTypeEnum;
use App\Models\Enums\EnergyTrackerUnitEnum;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(MeasurementDevice::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade');
            $table->string(MeasurementDevice::name);
            $table->string(MeasurementDevice::group)->nullable();
            $table->enum(MeasurementDevice::counting_type, EnergyTrackerCountingTypeEnum::allNames());
            $table->enum(MeasurementDevice::counting_method, EnergyTrackerCountingMethodEnum::allNames())
                ->default(EnergyTrackerCountingMethodEnum::default());
            $table->enum(MeasurementDevice::counting_unit, EnergyTrackerUnitEnum::allNames());
            $table->string(MeasurementDevice::meter_reading_value);
            $table->dateTime(MeasurementDevice::meter_reading_date);
            $table->string(MeasurementDevice::meter_description)->nullable();
            $table->integer(MeasurementDevice::decimal_places)->default(4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(MeasurementDevice::TABLE);
    }
};

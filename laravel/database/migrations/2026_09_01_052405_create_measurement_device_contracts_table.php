<?php

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Enums\EnergyTrackerContractBasePriceIntervalEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(MeasurementDeviceContract::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(MeasurementDeviceContract::measurement_device_id);
            $table->string(MeasurementDeviceContract::name);
            $table->string(MeasurementDeviceContract::provider)->nullable();
            $table->string(MeasurementDeviceContract::contract_number)->nullable();
            $table->date(MeasurementDeviceContract::starts_on)->nullable();
            $table->date(MeasurementDeviceContract::ends_on)->nullable();
            $table->decimal(MeasurementDeviceContract::base_price, 12, 4)->nullable();
            $table->enum(
                MeasurementDeviceContract::base_price_interval,
                EnergyTrackerContractBasePriceIntervalEnum::allNames(),
            )->nullable();
            $table->char(MeasurementDeviceContract::currency, 3)->default('EUR');
            $table->boolean(MeasurementDeviceContract::is_active)->default(false);
            $table->unsignedBigInteger(MeasurementDeviceContract::active_measurement_device_id)
                ->nullable()
                ->virtualAs(sprintf(
                    'CASE WHEN %s = 1 THEN %s ELSE NULL END',
                    MeasurementDeviceContract::is_active,
                    MeasurementDeviceContract::measurement_device_id,
                ));
            $table->text(MeasurementDeviceContract::notes)->nullable();
            $table->timestamps();

            $table->foreign(
                MeasurementDeviceContract::measurement_device_id,
                'md_contracts_device_fk',
            )
                ->references(MeasurementDevice::id)
                ->on(MeasurementDevice::TABLE)
                ->cascadeOnDelete();
            $table->index([
                MeasurementDeviceContract::measurement_device_id,
                MeasurementDeviceContract::is_active,
            ], 'md_contracts_device_active_idx');
            $table->unique(
                MeasurementDeviceContract::active_measurement_device_id,
                'measurement_device_contracts_one_active_per_device',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(MeasurementDeviceContract::TABLE);
    }
};

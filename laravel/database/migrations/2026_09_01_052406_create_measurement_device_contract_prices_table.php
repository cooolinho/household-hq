<?php

use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(MeasurementDeviceContractPrice::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(MeasurementDeviceContractPrice::measurement_device_contract_id);
            $table->decimal(MeasurementDeviceContractPrice::unit_price, 12, 6);
            $table->date(MeasurementDeviceContractPrice::valid_from);
            $table->text(MeasurementDeviceContractPrice::notes)->nullable();
            $table->timestamps();

            $table->unique([
                MeasurementDeviceContractPrice::measurement_device_contract_id,
                MeasurementDeviceContractPrice::valid_from,
            ], 'measurement_device_contract_prices_valid_from_unique');
            $table->foreign(
                MeasurementDeviceContractPrice::measurement_device_contract_id,
                'md_contract_prices_contract_fk',
            )
                ->references(MeasurementDeviceContract::id)
                ->on(MeasurementDeviceContract::TABLE)
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(MeasurementDeviceContractPrice::TABLE);
    }
};

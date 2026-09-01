<?php

use App\Models\ContactPerson;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractContact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(MeasurementDeviceContractContact::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger(MeasurementDeviceContractContact::measurement_device_contract_id);
            $table->unsignedBigInteger(MeasurementDeviceContractContact::contact_person_id);

            $table->unique([
                MeasurementDeviceContractContact::measurement_device_contract_id,
                MeasurementDeviceContractContact::contact_person_id,
            ], 'measurement_device_contract_contacts_unique');
            $table->foreign(
                MeasurementDeviceContractContact::measurement_device_contract_id,
                'md_contract_contacts_contract_fk',
            )
                ->references(MeasurementDeviceContract::id)
                ->on(MeasurementDeviceContract::TABLE)
                ->cascadeOnDelete();
            $table->foreign(
                MeasurementDeviceContractContact::contact_person_id,
                'md_contract_contacts_person_fk',
            )
                ->references(ContactPerson::id)
                ->on(ContactPerson::TABLE)
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists(MeasurementDeviceContractContact::TABLE);
    }
};

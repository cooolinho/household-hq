<?php

use App\Models\Enums\InsuranceTypeEnum;
use App\Models\Financial\Insurance;
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
        Schema::create(Insurance::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, Insurance::user_id)
                ->constrained()
                ->onDelete('cascade');
            $table->string(Insurance::name);
            $table->string(Insurance::number)->nullable();
            $table->date(Insurance::start_date)->nullable();
            $table->date(Insurance::end_date)->nullable();
            $table->string(Insurance::company)->nullable();
            $table->string(Insurance::contact_person)->nullable();
            $table->string(Insurance::phone)->nullable();
            $table->string(Insurance::email)->nullable();
            $table->enum(Insurance::type, InsuranceTypeEnum::allNames())->nullable();

            // address columns
            $table->string(Insurance::address_line_1)->nullable();
            $table->string(Insurance::address_line_2)->nullable();
            $table->string(Insurance::address_zip)->nullable();
            $table->string(Insurance::address_city)->nullable();
            $table->string(Insurance::address_country)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Insurance::TABLE);
    }
};

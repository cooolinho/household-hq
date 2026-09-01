<?php

use App\Models\ContactPerson;
use App\Models\Enums\ContactPersonTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(ContactPerson::TABLE, function (Blueprint $table) {
            $table->enum(ContactPerson::type, ContactPersonTypeEnum::allNames())
                ->default(ContactPersonTypeEnum::default())
                ->after(ContactPerson::role);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table(ContactPerson::TABLE, function (Blueprint $table) {
            $table->dropColumn(ContactPerson::type);
        });
    }
};

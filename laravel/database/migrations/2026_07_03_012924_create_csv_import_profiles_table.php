<?php

use App\Models\Financial\CSVImportProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(CSVImportProfile::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(CSVImportProfile::name);
            $table->string(CSVImportProfile::bank)->nullable();
            $table->string(CSVImportProfile::delimiter, 1)->default(';');
            $table->string(CSVImportProfile::enclosure, 1)->default('"');
            $table->string(CSVImportProfile::escape, 1)->default('');
            $table->tinyInteger(CSVImportProfile::offset_header)->default(0);
            $table->json(CSVImportProfile::mapping)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CSVImportProfile::TABLE);
    }
};

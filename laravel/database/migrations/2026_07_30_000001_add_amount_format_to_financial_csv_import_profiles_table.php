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
        Schema::table(CSVImportProfile::TABLE, function (Blueprint $table) {
            $table->string(CSVImportProfile::amount_format, 16)
                ->default('de_de')
                ->after(CSVImportProfile::offset_header);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(CSVImportProfile::TABLE, function (Blueprint $table) {
            $table->dropColumn(CSVImportProfile::amount_format);
        });
    }
};


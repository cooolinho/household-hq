<?php

use App\Models\Financial\CSVImportProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(CSVImportProfile::TABLE, function (Blueprint $table) {
            // Transaction-Feld => CSV-Spaltenname; ergänzt das bestehende Index-Mapping (mapping)
            $table->json(CSVImportProfile::header_mapping)
                ->nullable()
                ->after(CSVImportProfile::mapping);
            // alle Spaltennamen der CSV, mit der das Profil gespeichert wurde (Erkennung neuer Spalten)
            $table->json(CSVImportProfile::header_columns)
                ->nullable()
                ->after(CSVImportProfile::header_mapping);
            $table->string(CSVImportProfile::encoding, 32)
                ->default('auto')
                ->after(CSVImportProfile::escape);
            $table->boolean(CSVImportProfile::has_header)
                ->default(true)
                ->after(CSVImportProfile::encoding);
            $table->string(CSVImportProfile::date_format, 32)
                ->nullable()
                ->after(CSVImportProfile::amount_format);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(CSVImportProfile::TABLE, function (Blueprint $table) {
            $table->dropColumn([
                CSVImportProfile::header_mapping,
                CSVImportProfile::header_columns,
                CSVImportProfile::encoding,
                CSVImportProfile::has_header,
                CSVImportProfile::date_format,
            ]);
        });
    }
};

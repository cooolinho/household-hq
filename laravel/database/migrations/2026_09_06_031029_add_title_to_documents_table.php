<?php

use App\Models\Document;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(Document::TABLE, function (Blueprint $table) {
            $table->string(Document::download_filename)->nullable()->after(Document::type);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(Document::TABLE, function (Blueprint $table) {
            $table->dropColumn(Document::download_filename);
        });
    }
};

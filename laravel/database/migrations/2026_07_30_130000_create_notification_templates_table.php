<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(NotificationTemplate::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(NotificationTemplate::template_key)->unique();
            $table->text(NotificationTemplate::value)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(NotificationTemplate::TABLE);
    }
};


<?php

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(Reminder::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, Reminder::user_id)
                ->constrained(User::TABLE)
                ->cascadeOnDelete();
            $table->string(Reminder::name);
            $table->string(Reminder::remindable_type)->nullable();
            $table->unsignedBigInteger(Reminder::remindable_id)->nullable();
            $table->string(Reminder::date_property)->nullable();
            $table->text(Reminder::message)->nullable();
            $table->boolean(Reminder::send_mail)->default(true);
            $table->boolean(Reminder::send_notification)->default(true);
            $table->boolean(Reminder::enabled)->default(true);

            $table->timestamps();

            $table->index([
                Reminder::remindable_type,
                Reminder::remindable_id,
            ], 'reminders_remindable_type_remindable_id_index');
            $table->index([
                Reminder::user_id,
                Reminder::enabled,
            ], 'reminders_user_id_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Reminder::TABLE);
    }
};

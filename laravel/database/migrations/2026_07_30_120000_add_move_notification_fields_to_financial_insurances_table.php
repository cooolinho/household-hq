<?php

use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
use App\Models\Enums\InsuranceMoveNotificationStatusEnum;
use App\Models\Financial\Insurance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(Insurance::TABLE, function (Blueprint $table) {
            $table->timestamp(Insurance::move_notified_at)->nullable()->after(Insurance::email);
            $table->enum(Insurance::move_notification_channel, InsuranceMoveNotificationChannelEnum::allNames())->nullable()->after(Insurance::move_notified_at);
            $table->enum(Insurance::move_notification_status, InsuranceMoveNotificationStatusEnum::allNames())->nullable()->after(Insurance::move_notification_channel);
            $table->text(Insurance::move_notification_note)->nullable()->after(Insurance::move_notification_status);
        });
    }

    public function down(): void
    {
        Schema::table(Insurance::TABLE, function (Blueprint $table) {
            $table->dropColumn([
                Insurance::move_notified_at,
                Insurance::move_notification_channel,
                Insurance::move_notification_status,
                Insurance::move_notification_note,
            ]);
        });
    }
};


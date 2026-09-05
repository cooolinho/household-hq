<?php

use App\Models\CustomDashboardUserWidget;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(CustomDashboardUserWidget::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, CustomDashboardUserWidget::user_id)
                ->constrained(User::TABLE)
                ->cascadeOnDelete();
            $table->string(CustomDashboardUserWidget::title);
            $table->string(CustomDashboardUserWidget::navigation_group, 64);
            $table->string(CustomDashboardUserWidget::template_key, 128);
            $table->string(CustomDashboardUserWidget::widget_type, 16);
            $table->json(CustomDashboardUserWidget::configuration);
            $table->string(CustomDashboardUserWidget::width, 8)
                ->default(CustomDashboardUserWidget::WIDTH_FULL);
            $table->unsignedInteger(CustomDashboardUserWidget::sort)->default(0);
            $table->boolean(CustomDashboardUserWidget::is_active)->default(true);
            $table->index([
                CustomDashboardUserWidget::user_id,
                CustomDashboardUserWidget::is_active,
                CustomDashboardUserWidget::sort,
            ]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(CustomDashboardUserWidget::TABLE);
    }
};

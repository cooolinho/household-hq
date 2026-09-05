<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Filament\Admin\Widgets\Dashboard\Concerns\LoadsCustomDashboardUserWidget;
use App\Models\CustomDashboardUserWidget;
use App\Services\Dashboard\Widgets\Types\TableWidgetService;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CustomDashboardTableWidget extends TableWidget
{
    use LoadsCustomDashboardUserWidget;

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        $widget = $this->getCustomDashboardWidget();

        if ($widget === null) {
            return $table
                ->heading('Dashboard-Widget')
                ->query(CustomDashboardUserWidget::query()->whereKey(0))
                ->paginated(false)
                ->columns([]);
        }

        return app(TableWidgetService::class)->configure($widget, $table);
    }
}

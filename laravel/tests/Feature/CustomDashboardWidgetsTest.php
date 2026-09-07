<?php

namespace Tests\Feature;

use App\Filament\App\Clusters\Settings\Pages\DashboardSettingsPage;
use App\Filament\App\Pages\Dashboard;
use App\Menu\NavigationGroup;
use App\Models\CustomDashboardUserWidget;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_edit_a_custom_widget_through_dashboard_settings(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(DashboardSettingsPage::class);

        $component->callTableAction('createCustomWidget', null, [
            CustomDashboardUserWidget::title => 'Mein Bilanztrend',
            CustomDashboardUserWidget::navigation_group => NavigationGroup::BANKS->name,
            CustomDashboardUserWidget::template_key => 'bank-balance-trend',
            CustomDashboardUserWidget::width => CustomDashboardUserWidget::WIDTH_HALF,
            CustomDashboardUserWidget::sort => 4,
            CustomDashboardUserWidget::is_active => true,
            CustomDashboardUserWidget::configuration => [
                'currency' => 'eur',
                'months' => 6,
            ],
        ]);

        $widget = CustomDashboardUserWidget::query()->firstOrFail();

        self::assertSame(CustomDashboardWidgetTypeEnum::CHART, $widget->widget_type);
        self::assertSame('EUR', $widget->configuration['currency']);
        self::assertSame(6, $widget->configuration['months']);

        $component->callTableAction('editCustomWidget', $widget, [
            CustomDashboardUserWidget::title => 'Mein geänderter Bilanztrend',
            CustomDashboardUserWidget::navigation_group => NavigationGroup::BANKS->name,
            CustomDashboardUserWidget::template_key => 'bank-balance-trend',
            CustomDashboardUserWidget::width => CustomDashboardUserWidget::WIDTH_FULL,
            CustomDashboardUserWidget::sort => 1,
            CustomDashboardUserWidget::is_active => false,
            CustomDashboardUserWidget::configuration => [
                'currency' => 'USD',
                'months' => 12,
            ],
        ]);

        $widget->refresh();

        self::assertSame('Mein geänderter Bilanztrend', $widget->title);
        self::assertFalse($widget->is_active);
        self::assertSame(CustomDashboardUserWidget::WIDTH_FULL, $widget->width);
        self::assertSame('USD', $widget->configuration['currency']);
        self::assertSame(12, $widget->configuration['months']);

        $component->callTableAction('deleteCustomWidget', $widget);

        $this->assertDatabaseMissing(CustomDashboardUserWidget::TABLE, [
            CustomDashboardUserWidget::id => $widget->id,
        ]);
    }

    public function test_dashboard_only_loads_active_widgets_of_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownWidget = CustomDashboardUserWidget::query()->create([
            CustomDashboardUserWidget::user_id => $user->id,
            CustomDashboardUserWidget::title => 'Eigenes Widget',
            CustomDashboardUserWidget::navigation_group => NavigationGroup::BANKS->name,
            CustomDashboardUserWidget::template_key => 'bank-balance-trend',
            CustomDashboardUserWidget::widget_type => CustomDashboardWidgetTypeEnum::CHART,
            CustomDashboardUserWidget::configuration => [
                'currency' => 'EUR',
                'months' => 6,
            ],
            CustomDashboardUserWidget::width => CustomDashboardUserWidget::WIDTH_HALF,
            CustomDashboardUserWidget::sort => 1,
            CustomDashboardUserWidget::is_active => true,
        ]);

        CustomDashboardUserWidget::query()->create([
            CustomDashboardUserWidget::user_id => $user->id,
            CustomDashboardUserWidget::title => 'Inaktives Widget',
            CustomDashboardUserWidget::navigation_group => NavigationGroup::BANKS->name,
            CustomDashboardUserWidget::template_key => 'bank-balance-trend',
            CustomDashboardUserWidget::widget_type => CustomDashboardWidgetTypeEnum::CHART,
            CustomDashboardUserWidget::configuration => [],
            CustomDashboardUserWidget::width => CustomDashboardUserWidget::WIDTH_FULL,
            CustomDashboardUserWidget::sort => 2,
            CustomDashboardUserWidget::is_active => false,
        ]);

        CustomDashboardUserWidget::query()->create([
            CustomDashboardUserWidget::user_id => $otherUser->id,
            CustomDashboardUserWidget::title => 'Fremdes Widget',
            CustomDashboardUserWidget::navigation_group => NavigationGroup::BANKS->name,
            CustomDashboardUserWidget::template_key => 'bank-balance-trend',
            CustomDashboardUserWidget::widget_type => CustomDashboardWidgetTypeEnum::CHART,
            CustomDashboardUserWidget::configuration => [],
            CustomDashboardUserWidget::width => CustomDashboardUserWidget::WIDTH_FULL,
            CustomDashboardUserWidget::sort => 1,
            CustomDashboardUserWidget::is_active => true,
        ]);

        $widgets = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->instance()
            ->getWidgets();

        $customWidgetIds = collect($widgets)
            ->filter(fn(mixed $widget): bool => method_exists($widget, 'getProperties'))
            ->map(fn($widget): int => (int)$widget->getProperties()['customWidgetId'])
            ->values()
            ->all();

        self::assertSame([$ownWidget->id], $customWidgetIds);
    }
}

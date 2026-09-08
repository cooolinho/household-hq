<?php

namespace Tests\Feature;

use App\Filament\App\Pages\FixedCostStatisticsPage;
use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsBookingsTableWidget;
use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsCategoryChartWidget;
use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsOverviewWidget;
use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsTrendChartWidget;
use App\Models\DashboardWidgetPreference;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostStatisticsPeriodEnum;
use App\Models\Financial\FixedCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FixedCostStatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_renders_with_the_default_period(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FixedCostStatisticsPage::class)
            ->assertOk()
            ->assertSeeLivewire(FixedCostStatisticsOverviewWidget::class)
            ->assertSeeLivewire(FixedCostStatisticsTrendChartWidget::class)
            ->assertSeeLivewire(FixedCostStatisticsCategoryChartWidget::class)
            ->assertSeeLivewire(FixedCostStatisticsBookingsTableWidget::class);
    }

    public function test_changing_the_preset_updates_the_filters(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FixedCostStatisticsPage::class)
            ->set('filters.' . FixedCostStatisticsPage::FILTER_PRESET, FixedCostStatisticsPeriodEnum::CURRENT_MONTH->name)
            ->assertOk();
    }

    public function test_a_custom_range_is_applied_to_the_widgets(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-11-15',
        ]);

        $filters = [
            FixedCostStatisticsPage::FILTER_PRESET => FixedCostStatisticsPeriodEnum::CUSTOM->name,
            FixedCostStatisticsPage::FILTER_FROM => '2026-11-01',
            FixedCostStatisticsPage::FILTER_TO => '2026-11-30',
            FixedCostStatisticsPage::FILTER_INCLUDE_BUDGETS => false,
        ];

        Livewire::actingAs($user)
            ->test(FixedCostStatisticsOverviewWidget::class, ['pageFilters' => $filters])
            ->assertOk()
            ->assertSee('700,00');
    }

    public function test_the_budget_toggle_in_the_filter_form_persists_the_preference(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FixedCostStatisticsPage::class)
            ->set('filters.' . FixedCostStatisticsPage::FILTER_INCLUDE_BUDGETS, false);

        $this->assertDatabaseHas(DashboardWidgetPreference::TABLE, [
            DashboardWidgetPreference::user_id => $user->id,
            DashboardWidgetPreference::include_budgets_in_balance => 0,
        ]);
    }

    public function test_the_bookings_table_lists_projected_bookings_in_the_range(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-11-15',
        ]);

        $filters = [
            FixedCostStatisticsPage::FILTER_PRESET => FixedCostStatisticsPeriodEnum::CUSTOM->name,
            FixedCostStatisticsPage::FILTER_FROM => '2026-11-01',
            FixedCostStatisticsPage::FILTER_TO => '2026-11-30',
        ];

        Livewire::actingAs($user)
            ->test(FixedCostStatisticsBookingsTableWidget::class, ['pageFilters' => $filters])
            ->assertOk()
            ->assertSee('15.11.2026');
    }

    public function test_an_absurd_custom_range_is_clamped(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-11-15',
        ]);

        $filters = [
            FixedCostStatisticsPage::FILTER_PRESET => FixedCostStatisticsPeriodEnum::CUSTOM->name,
            FixedCostStatisticsPage::FILTER_FROM => '1990-01-01',
            FixedCostStatisticsPage::FILTER_TO => '2090-01-01',
        ];

        Livewire::actingAs($user)
            ->test(FixedCostStatisticsBookingsTableWidget::class, ['pageFilters' => $filters])
            ->assertOk();

        Livewire::actingAs($user)
            ->test(FixedCostStatisticsTrendChartWidget::class, ['pageFilters' => $filters])
            ->assertOk();
    }
}

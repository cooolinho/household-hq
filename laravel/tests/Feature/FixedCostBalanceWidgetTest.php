<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostBalanceWidget;
use App\Models\DashboardWidgetPreference;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\Budget;
use App\Models\Financial\FixedCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FixedCostBalanceWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_balance_including_budgets_by_default(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => now()->addDays(5)->toDateString(),
        ]);

        Budget::query()->create([
            Budget::user_id => $user->id,
            Budget::name => 'Lebensmittel',
            Budget::amount => 300,
            Budget::period => BudgetPeriodEnum::MONTHLY->name,
            Budget::active => true,
            Budget::include_in_balance => true,
        ]);

        Livewire::actingAs($user)
            ->test(FixedCostBalanceWidget::class)
            ->assertSet('includeBudgets', true)
            ->assertSee('1.000,00');
    }

    public function test_toggling_the_switch_persists_the_preference(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FixedCostBalanceWidget::class)
            ->set('includeBudgets', false);

        $this->assertDatabaseHas(DashboardWidgetPreference::TABLE, [
            DashboardWidgetPreference::user_id => $user->id,
            DashboardWidgetPreference::include_budgets_in_balance => 0,
        ]);
    }

    public function test_it_reads_the_persisted_preference_on_mount(): void
    {
        $user = User::factory()->create();

        DashboardWidgetPreference::forUser($user->id)->update([
            DashboardWidgetPreference::include_budgets_in_balance => false,
        ]);

        Livewire::actingAs($user)
            ->test(FixedCostBalanceWidget::class)
            ->assertSet('includeBudgets', false);
    }

    public function test_it_renders_even_when_a_fixed_cost_has_a_broken_custom_interval(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Kaputt',
            FixedCost::amount => -50,
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => null,
            FixedCost::custom_interval_unit => null,
            FixedCost::next_booking_date => now()->addDays(5)->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(FixedCostBalanceWidget::class)
            ->assertOk();
    }
}

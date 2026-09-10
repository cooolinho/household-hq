<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostBalanceWidget;
use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostLiquidityWidget;
use App\Models\DashboardWidgetPreference;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FixedCostLiquidityWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_amount_still_required_until_period_end(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Strom',
            FixedCost::amount => -100,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-20',
        ]);

        Livewire::actingAs($user)
            ->test(FixedCostLiquidityWidget::class)
            ->assertOk()
            ->assertSee('100,00');
    }

    public function test_toggling_the_switch_persists_and_syncs_with_the_balance_widget(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FixedCostLiquidityWidget::class)
            ->set('includeBudgets', false);

        $this->assertDatabaseHas(DashboardWidgetPreference::TABLE, [
            DashboardWidgetPreference::user_id => $user->id,
            DashboardWidgetPreference::include_budgets_in_balance => 0,
        ]);

        // Geschwister-Widget liest dieselbe Präferenz beim Mount.
        Livewire::actingAs($user)
            ->test(FixedCostBalanceWidget::class)
            ->assertSet('includeBudgets', false);
    }

    public function test_it_respects_a_custom_period_start_day(): void
    {
        $user = User::factory()->create();

        // Periodenstart 25. -> ab "heute" (15.09.) endet die laufende Periode bereits am 24.09.,
        // statt wie im Kalendermonat üblich erst am 30.09.
        DashboardWidgetPreference::forUser($user->id)->update([
            DashboardWidgetPreference::period_start_day => 25,
        ]);

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-28',
        ]);

        Livewire::actingAs($user)
            ->test(FixedCostLiquidityWidget::class)
            ->assertOk()
            ->assertSee('Keine ausstehenden Fixkosten-Buchungen');
    }

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-15'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}

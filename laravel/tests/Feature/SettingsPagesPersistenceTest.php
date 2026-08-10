<?php

namespace Tests\Feature;

use App\Filament\Admin\Clusters\Settings\Pages\DashboardSettingsPage;
use App\Filament\Admin\Clusters\Settings\Pages\FixedCostSettingsPage;
use App\Filament\Admin\Clusters\Settings\Pages\ImapSettingsPage;
use App\Models\DashboardWidgetPreference;
use App\Models\User;
use App\Settings\FixedCostSettings;
use App\Settings\ImapImportSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPagesPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_cost_settings_page_saves_values_to_database_settings(): void
    {
        Livewire::test(FixedCostSettingsPage::class)
            ->set('data.matching_threshold', 88)
            ->set('data.reminders_enabled', false)
            ->set('data.recurring_window_months', 6)
            ->call('save')
            ->assertHasNoErrors();

        $settings = app(FixedCostSettings::class)->refresh();

        $this->assertSame(88, $settings->matching_threshold);
        $this->assertFalse($settings->reminders_enabled);
        $this->assertSame(6, $settings->recurring_window_months);
    }

    public function test_imap_settings_page_saves_values_to_database_settings(): void
    {
        Livewire::test(ImapSettingsPage::class)
            ->set('data.enabled', false)
            ->set('data.schedule_minutes', 22)
            ->call('save')
            ->assertHasNoErrors();

        $settings = app(ImapImportSettings::class)->refresh();

        $this->assertFalse($settings->enabled);
        $this->assertSame(22, $settings->schedule_minutes);
    }

    public function test_dashboard_settings_page_saves_values_to_user_preferences(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DashboardSettingsPage::class)
            ->set('data.' . DashboardWidgetPreference::show_monthly_balance_stats, false)
            ->set('data.' . DashboardWidgetPreference::show_monthly_balance_chart, true)
            ->set('data.' . DashboardWidgetPreference::show_upcoming_transactions_table, false)
            ->set('data.' . DashboardWidgetPreference::show_portfolio_overview, true)
            ->set('data.' . DashboardWidgetPreference::balance_mode, DashboardWidgetPreference::BALANCE_MODE_BOTH)
            ->set('data.' . DashboardWidgetPreference::currency, 'eur')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(DashboardWidgetPreference::TABLE, [
            DashboardWidgetPreference::user_id => $user->id,
            DashboardWidgetPreference::show_monthly_balance_stats => 0,
            DashboardWidgetPreference::show_monthly_balance_chart => 1,
            DashboardWidgetPreference::show_upcoming_transactions_table => 0,
            DashboardWidgetPreference::show_portfolio_overview => 1,
            DashboardWidgetPreference::balance_mode => DashboardWidgetPreference::BALANCE_MODE_BOTH,
            DashboardWidgetPreference::currency => 'EUR',
        ]);
    }
}


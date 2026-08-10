<?php

namespace Tests\Feature;

use App\Filament\Admin\Clusters\Settings\Pages\FixedCostSettingsPage;
use App\Filament\Admin\Clusters\Settings\Pages\ImapSettingsPage;
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
}


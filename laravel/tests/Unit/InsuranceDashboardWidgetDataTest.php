<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\Financial\Insurances\Widgets\InsuranceDashboardWidget;
use App\Models\Financial\Insurance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InsuranceDashboardWidgetDataTest extends TestCase
{
    public function test_it_builds_dashboard_data_with_default_lookahead_window(): void
    {
        $widget = new class extends InsuranceDashboardWidget {
            public function exposeBuildDashboardDataFromInsurances(Collection $insurances, CarbonImmutable $today): array
            {
                return $this->buildDashboardDataFromInsurances($insurances, $today);
            }
        };

        $today = CarbonImmutable::parse('2026-07-29');

        $data = $widget->exposeBuildDashboardDataFromInsurances(collect([
            $this->makeInsurance('Hausrat', '2026-07-29'),
            $this->makeInsurance('Haftpflicht', '2026-08-05'),
            $this->makeInsurance('Rechtsschutz', '2026-08-31'),
            $this->makeInsurance('Kfz', '2026-07-20'),
            $this->makeInsurance('Unfall', null),
        ]), $today);

        $this->assertSame(5, $data['totalInsurances']);
        $this->assertSame(2, $data['expiringSoonCount']);
        $this->assertSame(1, $data['expiredCount']);
        $this->assertSame(1, $data['withoutEndDateCount']);
    }

    private function makeInsurance(string $name, ?string $endDate): Insurance
    {
        $insurance = new Insurance();
        $insurance->forceFill([
            Insurance::name => $name,
            Insurance::company => 'Test AG',
            Insurance::end_date => $endDate,
        ]);

        return $insurance;
    }

    public function test_it_respects_configurable_lookahead_days(): void
    {
        $widget = new class extends InsuranceDashboardWidget {
            public function exposeBuildDashboardDataFromInsurances(Collection $insurances, CarbonImmutable $today): array
            {
                return $this->buildDashboardDataFromInsurances($insurances, $today);
            }
        };

        $widget->lookaheadDays = 45;
        $today = CarbonImmutable::parse('2026-07-29');

        $data = $widget->exposeBuildDashboardDataFromInsurances(collect([
            $this->makeInsurance('Hausrat', '2026-07-29'),
            $this->makeInsurance('Haftpflicht', '2026-08-05'),
            $this->makeInsurance('Rechtsschutz', '2026-08-31'),
        ]), $today);

        $this->assertSame(3, $data['expiringSoonCount']);
    }
}


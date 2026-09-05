<?php

namespace Tests\Unit;

use App\Menu\NavigationGroup;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplateRegistry;
use Tests\TestCase;

class CustomDashboardWidgetTemplateRegistryTest extends TestCase
{
    public function test_every_navigation_group_exposes_templates(): void
    {
        $registry = app(CustomDashboardWidgetTemplateRegistry::class);

        foreach (NavigationGroup::cases() as $group) {
            self::assertNotEmpty($registry->templateOptions($group->name));
        }
    }

    public function test_template_type_and_configuration_are_resolved_from_the_registry(): void
    {
        $template = app(CustomDashboardWidgetTemplateRegistry::class)
            ->get(NavigationGroup::BANKS->name, 'bank-balance-trend');

        self::assertSame(CustomDashboardWidgetTypeEnum::CHART, $template->type);
        self::assertSame(
            [
                'currency' => 'EUR',
                'months' => 24,
            ],
            $template->normalizeConfiguration([
                'currency' => 'eur',
                'months' => 60,
                'unexpected' => 'ignored',
            ]),
        );
    }
}

<?php

namespace App\Services\Dashboard\Widgets;

use App\Filament\Admin\Widgets\Dashboard\CustomDashboardChartWidget;
use App\Filament\Admin\Widgets\Dashboard\CustomDashboardStatsWidget;
use App\Filament\Admin\Widgets\Dashboard\CustomDashboardTableWidget;
use App\Menu\NavigationGroup;
use App\Models\CustomDashboardUserWidget;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Services\Dashboard\Widgets\Templates\BanksDashboardWidgetTemplates;
use App\Services\Dashboard\Widgets\Templates\EnergyTrackerDashboardWidgetTemplates;
use App\Services\Dashboard\Widgets\Templates\FeaturesDashboardWidgetTemplates;
use App\Services\Dashboard\Widgets\Templates\FixedCostsDashboardWidgetTemplates;
use App\Services\Dashboard\Widgets\Templates\InsurancesDashboardWidgetTemplates;
use App\Services\Dashboard\Widgets\Templates\InventoryDashboardWidgetTemplates;
use App\Services\Dashboard\Widgets\Templates\RemindersDashboardWidgetTemplates;
use Filament\Schemas\Components\Component;
use Filament\Widgets\Widget;
use InvalidArgumentException;

final class CustomDashboardWidgetTemplateRegistry
{
    /**
     * @var array<string, class-string>
     */
    private const array TEMPLATE_CLASSES = [
        'BANKS' => BanksDashboardWidgetTemplates::class,
        'FIXED_COSTS' => FixedCostsDashboardWidgetTemplates::class,
        'INSURANCES' => InsurancesDashboardWidgetTemplates::class,
        'ENERGY_TRACKER' => EnergyTrackerDashboardWidgetTemplates::class,
        'INVENTORY' => InventoryDashboardWidgetTemplates::class,
        'FEATURES' => FeaturesDashboardWidgetTemplates::class,
        'REMINDERS' => RemindersDashboardWidgetTemplates::class,
    ];

    /**
     * @return array<string, string>
     */
    public function groupOptions(): array
    {
        $options = [];

        foreach (NavigationGroup::cases() as $group) {
            $options[$group->name] = $group->getLabel();
        }

        return $options;
    }

    public function groupLabel(string $groupName): ?string
    {
        foreach (NavigationGroup::cases() as $group) {
            if ($group->name === $groupName) {
                return $group->getLabel();
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function templateOptions(?string $groupName): array
    {
        if (blank($groupName)) {
            return [];
        }

        $options = [];

        foreach ($this->templatesFor($groupName) as $template) {
            $options[$template->key] = $template->label;
        }

        return $options;
    }

    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public function templatesFor(string $groupName): array
    {
        $templateClass = self::TEMPLATE_CLASSES[$groupName] ?? null;

        if ($templateClass === null) {
            return [];
        }

        return $templateClass::templates();
    }

    /**
     * @return array<int, Component>
     */
    public function configurationSchema(string $groupName, string $templateKey): array
    {
        return $this->find($groupName, $templateKey)?->configurationSchema() ?? [];
    }

    public function find(string $groupName, string $templateKey): ?CustomDashboardWidgetTemplate
    {
        foreach ($this->templatesFor($groupName) as $template) {
            if ($template->key === $templateKey) {
                return $template;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfiguration(string $groupName, string $templateKey): array
    {
        return $this->get($groupName, $templateKey)->defaultConfiguration();
    }

    public function get(string $groupName, string $templateKey): CustomDashboardWidgetTemplate
    {
        $template = $this->find($groupName, $templateKey);

        if ($template === null) {
            throw new InvalidArgumentException(
                "The dashboard template [{$templateKey}] is not available in group [{$groupName}].",
            );
        }

        return $template;
    }

    /**
     * @return class-string<Widget>|null
     */
    public function widgetClassFor(CustomDashboardUserWidget $widget): ?string
    {
        $template = $this->templateForWidget($widget);

        if ($template === null) {
            return null;
        }

        return match ($template->type) {
            CustomDashboardWidgetTypeEnum::CHART => CustomDashboardChartWidget::class,
            CustomDashboardWidgetTypeEnum::STAT => CustomDashboardStatsWidget::class,
            CustomDashboardWidgetTypeEnum::TABLE => CustomDashboardTableWidget::class,
        };
    }

    public function templateForWidget(CustomDashboardUserWidget $widget): ?CustomDashboardWidgetTemplate
    {
        $template = $this->find(
            (string)$widget->{CustomDashboardUserWidget::navigation_group},
            (string)$widget->{CustomDashboardUserWidget::template_key},
        );

        if ($template === null) {
            return null;
        }

        $widgetType = $widget->{CustomDashboardUserWidget::widget_type};
        $widgetType = $widgetType instanceof CustomDashboardWidgetTypeEnum
            ? $widgetType
            : CustomDashboardWidgetTypeEnum::tryFrom((string)$widgetType);

        return $widgetType === $template->type ? $template : null;
    }
}

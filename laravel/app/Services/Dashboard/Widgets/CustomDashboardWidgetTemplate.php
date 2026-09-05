<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Tables\Table;
use LogicException;

final class CustomDashboardWidgetTemplate
{
    /**
     * @param array<int, string> $configurationKeys
     * @param Closure(): array<int, Component> $configurationSchema
     * @param Closure(): array<string, mixed> $defaultConfiguration
     * @param Closure(int, array<string, mixed>): array<string, mixed>|array<int, array<string, mixed>> $dataResolver
     * @param Closure(Table, int, array<string, mixed>): Table|null $tableConfigurator
     * @param Closure(array<string, mixed>): array<string, mixed>|null $configurationNormalizer
     */
    public function __construct(
        public readonly string                        $key,
        public readonly string                        $label,
        public readonly CustomDashboardWidgetTypeEnum $type,
        private readonly array                        $configurationKeys,
        private readonly Closure                      $configurationSchema,
        private readonly Closure                      $defaultConfiguration,
        private readonly Closure                      $dataResolver,
        private readonly ?Closure                     $tableConfigurator = null,
        private readonly ?string                      $chartType = null,
        private readonly ?Closure                     $configurationNormalizer = null,
    )
    {
    }

    /**
     * @return array<int, Component>
     */
    public function configurationSchema(): array
    {
        return ($this->configurationSchema)();
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function resolveData(int $userId, array $configuration): array
    {
        return ($this->dataResolver)($userId, $this->normalizeConfiguration($configuration));
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    public function normalizeConfiguration(array $configuration): array
    {
        $configuration = array_replace(
            $this->defaultConfiguration(),
            array_intersect_key($configuration, array_flip($this->configurationKeys)),
        );

        if ($this->configurationNormalizer !== null) {
            $configuration = ($this->configurationNormalizer)($configuration);
        }

        return $configuration;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfiguration(): array
    {
        return ($this->defaultConfiguration)();
    }

    public function chartType(): ?string
    {
        return $this->chartType;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function configureTable(Table $table, int $userId, array $configuration): Table
    {
        if ($this->tableConfigurator === null) {
            throw new LogicException("Template [{$this->key}] does not provide a table renderer.");
        }

        return ($this->tableConfigurator)(
            $table,
            $userId,
            $this->normalizeConfiguration($configuration),
        );
    }
}

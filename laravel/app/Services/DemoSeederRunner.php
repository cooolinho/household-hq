<?php

namespace App\Services;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;
use SplFileInfo;

class DemoSeederRunner
{
    /**
     * @var array<string, int>
     */
    private const array GROUP_ORDER = [
        'Core' => 1,
        'EnergyTracker' => 2,
        'Financial' => 3,
    ];

    /**
     * @return array{
     *     kind: 'all'|'group'|'seeder',
     *     key: string,
     *     label: string,
     *     description: string,
     *     class?: class-string<Seeder>,
     *     classes?: list<class-string<Seeder>>,
     *     group?: string
     * }|null
     */
    public function findChoiceByIndex(int $index): ?array
    {
        if ($index < 1) {
            return null;
        }

        $choice = $this->choices()->get($index - 1);

        return is_array($choice) ? $choice : null;
    }

    /**
     * @return Collection<int, array{
     *     kind: 'all'|'group'|'seeder',
     *     key: string,
     *     label: string,
     *     description: string,
     *     class?: class-string<Seeder>,
     *     classes?: list<class-string<Seeder>>,
     *     group?: string
     * }>
     */
    public function choices(): Collection
    {
        $seeders = $this->all();
        $choices = collect();
        $rootSeeder = $seeders->firstWhere('class', DatabaseSeeder::class);

        if (is_array($rootSeeder)) {
            $choices->push([
                'kind' => 'all',
                'key' => DatabaseSeeder::class,
                'label' => 'Alle Demo-Daten',
                'description' => $rootSeeder['description'],
                'class' => DatabaseSeeder::class,
            ]);
        }

        $seeders
            ->reject(fn(array $seeder): bool => $seeder['class'] === DatabaseSeeder::class)
            ->groupBy('group')
            ->each(function (Collection $groupSeeders, string $group) use ($choices): void {
                $classes = $groupSeeders
                    ->pluck('class')
                    ->values()
                    ->all();

                $choices->push([
                    'kind' => 'group',
                    'key' => $group,
                    'label' => sprintf('Gruppe %s', $group),
                    'description' => sprintf('Führt alle %s-Seeder aus', $group),
                    'classes' => $classes,
                    'group' => $group,
                ]);

                foreach ($groupSeeders as $seeder) {
                    $choices->push([
                        'kind' => 'seeder',
                        'key' => $seeder['class'],
                        'label' => $seeder['basename'],
                        'description' => $seeder['description'],
                        'class' => $seeder['class'],
                        'group' => $group,
                    ]);
                }
            });

        return $choices->values();
    }

    /**
     * @return Collection<int, array{
     *     class: class-string<Seeder>,
     *     basename: string,
     *     group: string|null,
     *     description: string
     * }>
     */
    public function all(): Collection
    {
        return collect(File::allFiles(database_path('seeders')))
            ->map(function (SplFileInfo $file): ?array {
                if ($file->getExtension() !== 'php') {
                    return null;
                }

                $relativePath = str_replace('\\', '/', $file->getRelativePathname());
                $class = 'Database\\Seeders\\' . str_replace(
                        '/',
                        '\\',
                        substr($relativePath, 0, -4),
                    );

                if (!is_a($class, Seeder::class, true)) {
                    return null;
                }

                if (!method_exists($class, 'description')) {
                    throw new RuntimeException(sprintf(
                        'Seeder %s muss eine statische description(): string Methode besitzen.',
                        $class,
                    ));
                }

                $description = trim((string)$class::description());

                if ($description === '') {
                    throw new RuntimeException(sprintf(
                        'Seeder %s besitzt keine gültige Beschreibung.',
                        $class,
                    ));
                }

                $relativeDirectory = str_replace('\\', '/', $file->getRelativePath());
                $group = $relativeDirectory === ''
                    ? null
                    : explode('/', $relativeDirectory)[0];

                return [
                    'class' => $class,
                    'basename' => $file->getBasename('.php'),
                    'group' => $group,
                    'description' => $description,
                ];
            })
            ->filter()
            ->sort(function (array $left, array $right): int {
                if ($left['class'] === DatabaseSeeder::class) {
                    return -1;
                }

                if ($right['class'] === DatabaseSeeder::class) {
                    return 1;
                }

                $leftGroupOrder = self::GROUP_ORDER[$left['group'] ?? ''] ?? PHP_INT_MAX;
                $rightGroupOrder = self::GROUP_ORDER[$right['group'] ?? ''] ?? PHP_INT_MAX;

                return [$leftGroupOrder, $left['group'], $left['basename']]
                    <=> [$rightGroupOrder, $right['group'], $right['basename']];
            })
            ->values();
    }

    /**
     * Run one selected seeder and its dependencies.
     */
    public function run(string $class, Command $command): void
    {
        $seeders = $this->all();
        $availableClasses = $seeders->pluck('class')->all();

        if (!in_array($class, $availableClasses, true)) {
            throw new RuntimeException(sprintf('Der Seeder %s wurde nicht gefunden.', $class));
        }

        if ($class === DatabaseSeeder::class) {
            $this->invokeSeeder(DatabaseSeeder::class, $command);

            return;
        }

        $this->runClasses([$class], $availableClasses, $command);
    }

    private function invokeSeeder(string $class, Command $command): void
    {
        $seeder = app($class)
            ->setContainer(app())
            ->setCommand($command);

        $seeder->__invoke();
    }

    /**
     * @param list<class-string<Seeder>> $classes
     * @param list<class-string<Seeder>> $availableClasses
     */
    private function runClasses(array $classes, array $availableClasses, Command $command): void
    {
        $orderedClasses = [];
        $visited = [];
        $visiting = [];

        foreach ($classes as $class) {
            $this->appendWithDependencies(
                $class,
                $availableClasses,
                $orderedClasses,
                $visited,
                $visiting,
            );
        }

        $orchestrator = app(DatabaseSeeder::class)
            ->setContainer(app())
            ->setCommand($command);

        $orchestrator->call($orderedClasses);
    }

    /**
     * @param list<class-string<Seeder>> $availableClasses
     * @param list<class-string<Seeder>> $orderedClasses
     * @param array<class-string<Seeder>, bool> $visited
     * @param array<class-string<Seeder>, bool> $visiting
     */
    private function appendWithDependencies(
        string $class,
        array  $availableClasses,
        array  &$orderedClasses,
        array  &$visited,
        array  &$visiting,
    ): void
    {
        if (isset($visited[$class])) {
            return;
        }

        if (isset($visiting[$class])) {
            throw new RuntimeException(sprintf('Zyklische Seeder-Abhängigkeit bei %s.', $class));
        }

        if (!in_array($class, $availableClasses, true)) {
            throw new RuntimeException(sprintf('Die Seeder-Abhängigkeit %s wurde nicht gefunden.', $class));
        }

        $visiting[$class] = true;

        foreach ($this->dependenciesFor($class) as $dependency) {
            $this->appendWithDependencies(
                $dependency,
                $availableClasses,
                $orderedClasses,
                $visited,
                $visiting,
            );
        }

        unset($visiting[$class]);
        $visited[$class] = true;
        $orderedClasses[] = $class;
    }

    /**
     * @return list<class-string<Seeder>>
     */
    private function dependenciesFor(string $class): array
    {
        if (!method_exists($class, 'dependencies')) {
            return [];
        }

        return array_values($class::dependencies());
    }

    /**
     * @param list<class-string<Seeder>> $classes
     */
    public function runGroup(string $group, Command $command): void
    {
        $seeders = $this->all();
        $classes = $seeders
            ->where('group', $group)
            ->pluck('class')
            ->values()
            ->all();

        if ($classes === []) {
            throw new RuntimeException(sprintf('Die Seeder-Gruppe %s wurde nicht gefunden.', $group));
        }

        $this->runClasses($classes, $seeders->pluck('class')->all(), $command);
    }
}

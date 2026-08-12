<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileInfo;

class ScheduledJobRunner
{
    /**
     * @return array<class-string, string>
     */
    public function options(): array
    {
        return $this->all()
            ->mapWithKeys(fn(array $job): array => [$job['class'] => $job['display']])
            ->all();
    }

    /**
     * @return Collection<int, array{class: class-string, basename: string, label: string, display: string}>
     */
    public function all(): Collection
    {
        return collect(File::files(app_path('Jobs/Scheduled')))
            ->map(function (SplFileInfo $file): ?array {
                $basename = $file->getBasename('.php');
                $class = 'App\\Jobs\\Scheduled\\' . $basename;

                if (!class_exists($class)) {
                    return null;
                }

                if (!method_exists($class, 'description')) {
                    return null;
                }

                $label = Str::headline(Str::replaceLast('Job', '', $basename));
                $description = trim((string)$class::description());

                if ($description === '') {
                    return null;
                }

                return [
                    'class' => $class,
                    'basename' => $basename,
                    'label' => $label,
                    'display' => sprintf("%s (%s)", $description, $basename),
                ];
            })
            ->filter()
            ->sortBy('basename')
            ->values();
    }

    /**
     * @return array{class: class-string, basename: string, label: string, display: string}|null
     */
    public function findByIndex(int $index): ?array
    {
        if ($index < 1) {
            return null;
        }

        return $this->all()->get($index - 1);
    }

    public function run(string $class): void
    {
        if (!str_starts_with($class, 'App\\Jobs\\Scheduled\\')) {
            throw new RuntimeException('Nur Jobs aus App\\Jobs\\Scheduled können gestartet werden.');
        }

        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Die Klasse %s wurde nicht gefunden.', $class));
        }

        $job = app($class);

        if (!method_exists($job, 'handle')) {
            throw new RuntimeException(sprintf('Die Klasse %s besitzt keine handle-Methode.', $class));
        }

        if (!method_exists($job, 'dispatch')) {
            throw new RuntimeException(sprintf('Die Klasse %s besitzt keine dispatch-Methode.', $class));
        }

        $class::dispatch();

        Log::channel('database')->info(sprintf('Scheduled Job %s wurde manuell gestartet.', $class), [
            'event' => 'scheduled_job.started',
            'job_class' => $class,
        ]);
    }
}


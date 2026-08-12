<?php

namespace App\Console\Commands;

use App\Services\ScheduledJobRunner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('app:run-scheduled-job')]
#[Description('Führt einen Scheduled Job aus App\\Jobs\\Scheduled interaktiv aus')]
class RunScheduledJobCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ScheduledJobRunner $runner): int
    {
        $jobs = $runner->all();
        $selectedJob = null;

        if ($jobs->isEmpty()) {
            $this->error('Es wurden keine Scheduled Jobs in app/Jobs/Scheduled gefunden.');

            return self::FAILURE;
        }

        $this->info('Verfügbare Scheduled Jobs:');

        foreach ($jobs as $index => $job) {
            $this->line(sprintf('%d) %s', $index + 1, $job['display']));
        }

        while (true) {
            $input = trim((string)$this->ask('Welche Nummer möchtest du ausführen? (leer = Abbruch)'));

            if ($input === '') {
                $this->line('Abgebrochen.');

                return self::SUCCESS;
            }

            $index = filter_var($input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $jobs->count()]]);

            if ($index === false) {
                $this->warn(sprintf('Ungültige Auswahl "%s". Bitte eine Zahl zwischen 1 und %d eingeben.', $input, $jobs->count()));

                continue;
            }

            $selectedJob = $runner->findByIndex($index);

            if (is_array($selectedJob)) {
                break;
            }

            $this->warn(sprintf('Ungültige Auswahl "%s". Bitte eine Zahl zwischen 1 und %d eingeben.', $input, $jobs->count()));
        }

        $this->info(sprintf('Starte %s ...', $selectedJob['display']));

        try {
            $runner->run($selectedJob['class']);
        } catch (Throwable $e) {
            report($e);

            $this->error(sprintf('Der Job konnte nicht ausgeführt werden: %s', $e->getMessage()));

            return self::FAILURE;
        }

        $this->info('Job erfolgreich ausgeführt.');

        return self::SUCCESS;
    }
}

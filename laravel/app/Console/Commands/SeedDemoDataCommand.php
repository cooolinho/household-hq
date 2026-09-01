<?php

namespace App\Console\Commands;

use App\Services\DemoSeederRunner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('app:seed-demo-data')]
#[Description('Demo-Seeder interaktiv auswählen und ausführen')]
class SeedDemoDataCommand extends Command
{
    public function handle(DemoSeederRunner $runner): int
    {
        $choices = $runner->choices();

        if ($choices->isEmpty()) {
            $this->error('Es wurden keine Demo-Seeder gefunden.');

            return self::FAILURE;
        }

        $this->info('Verfügbare Demo-Seeder:');

        foreach ($choices as $index => $choice) {
            $indent = $choice['kind'] === 'seeder' ? '  ' : '';
            $this->line(sprintf(
                '%d) %s%s - %s',
                $index + 1,
                $indent,
                $choice['label'],
                $choice['description'],
            ));
        }

        while (true) {
            $input = trim((string)$this->ask('Welche Nummer möchtest du ausführen? (leer = Abbruch)'));

            if ($input === '') {
                $this->line('Abgebrochen.');

                return self::SUCCESS;
            }

            $index = filter_var($input, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 1,
                    'max_range' => $choices->count(),
                ],
            ]);

            if ($index === false) {
                $this->warn(sprintf(
                    'Ungültige Auswahl "%s". Bitte eine Zahl zwischen 1 und %d eingeben.',
                    $input,
                    $choices->count(),
                ));

                continue;
            }

            $selectedChoice = $runner->findChoiceByIndex($index);

            if (is_array($selectedChoice)) {
                break;
            }
        }

        $this->info(sprintf(
            'Starte %s ...',
            $selectedChoice['label'],
        ));

        try {
            if ($selectedChoice['kind'] === 'group') {
                $runner->runGroup($selectedChoice['group'], $this);
            } else {
                $runner->run($selectedChoice['class'], $this);
            }
        } catch (Throwable $e) {
            report($e);

            $this->error(sprintf(
                'Der Seeder konnte nicht ausgeführt werden: %s',
                $e->getMessage(),
            ));

            return self::FAILURE;
        }

        $this->info('Demo-Seeder erfolgreich ausgeführt.');

        return self::SUCCESS;
    }
}

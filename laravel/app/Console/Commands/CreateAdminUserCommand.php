<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Legt genau einen Administrator-Account an. Gedacht für den Erststart des
 * Docker-All-in-One-Images (ADMIN_EMAIL/ADMIN_PASSWORD), funktioniert aber
 * genauso interaktiv im lokalen Dev-Setup als Ersatz für
 * `filament:user` + manuelles Hochstufen per Tinker.
 *
 * Existiert die E-Mail bereits, bleibt der Account unangetastet - ein
 * Container-Neustart mit denselben ADMIN_*-Variablen darf kein Passwort
 * stillschweigend überschreiben.
 */
#[Signature('app:create-admin-user {--name=Administrator} {--email=} {--password=}')]
#[Description('Legt einen Administrator-Account an, sofern noch keiner mit dieser E-Mail existiert')]
class CreateAdminUserCommand extends Command
{
    public function handle(): int
    {
        $name = trim((string)$this->option('name'));
        $email = $this->resolveEmail();
        $password = $this->resolvePassword();

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (User::query()->where(User::email, $email)->exists()) {
            $this->info(sprintf('Benutzer "%s" existiert bereits, es wurde nichts verändert.', $email));

            return self::SUCCESS;
        }

        User::query()->create([
            User::name => $name,
            User::email => $email,
            // Der 'hashed'-Cast auf User::password hasht den Klartext beim Speichern.
            User::password => $password,
            User::email_verified_at => now(),
            User::role => Role::ADMIN,
            User::is_active => true,
        ]);

        $this->info(sprintf('Administrator "%s" wurde angelegt.', $email));

        return self::SUCCESS;
    }

    private function resolveEmail(): string
    {
        $email = trim((string)$this->option('email'));

        // ->ask() prüft Interaktivität nicht selbst, bevor es die Frage stellt
        // (das übernimmt erst der QuestionHelper dahinter) - im Container läuft
        // dieser Befehl ohne TTY, daher hier explizit prüfen und sonst gleich
        // mit dem leeren Wert in die Validierung laufen.
        if ($email === '' && $this->input->isInteractive()) {
            $email = trim((string)$this->ask('E-Mail-Adresse des Administrators'));
        }

        return $email;
    }

    private function resolvePassword(): string
    {
        $password = (string)$this->option('password');

        if ($password === '' && $this->input->isInteractive()) {
            $password = (string)$this->secret('Passwort des Administrators');
        }

        return $password;
    }
}

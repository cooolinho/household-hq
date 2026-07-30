<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Mail;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$recipient = $argv[1] ?? 'dev-box1@cooolinho.de';
$sender = config('mail.from.address');

if (empty($sender)) {
    fwrite(STDERR, "MAIL_FROM_ADDRESS ist nicht gesetzt. Bitte zuerst in .env konfigurieren.\n");
    exit(1);
}

$subject = sprintf('[SMTP-Test] %s', now()->format('Y-m-d H:i:s'));
$body = implode("\n", [
    'Das ist eine automatische SMTP-Testmail aus personal-home-portal.',
    sprintf('Zeitpunkt: %s', now()->toDateTimeString()),
    sprintf('Empfaenger: %s', $recipient),
    sprintf('Sender (mail.from.address): %s', $sender),
]);

try {
    Mail::raw($body, function ($message) use ($recipient, $subject): void {
        $message->to($recipient)->subject($subject);
    });

    echo "SMTP-Testmail wurde versendet.\n";
    echo "Empfaenger: {$recipient}\n";
    echo "Betreff: {$subject}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "SMTP-Test fehlgeschlagen: {$e->getMessage()}\n");
    exit(1);
}


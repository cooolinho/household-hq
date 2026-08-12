<?php

namespace App\Logging;

use App\Models\ApplicationLog;
use Illuminate\Support\Facades\Auth;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DatabaseLogHandler extends AbstractProcessingHandler
{
    private const int MAX_CONTEXT_DEPTH = 6;
    private const int MAX_CONTEXT_ITEMS = 150;
    private const int MAX_STRING_LENGTH = 4000;

    /**
     * @var array<int, string>
     */
    private const array SENSITIVE_KEYS = [
        'password',
        'passwd',
        'pwd',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'api_key',
        'cookie',
        'set-cookie',
    ];

    public function __construct(int|string|Level $level = Level::Info, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $context = $this->sanitizeContext($record->context, 0);

        try {
            ApplicationLog::query()->create([
                ApplicationLog::user_id => $this->resolveUserId($context),
                ApplicationLog::event => $this->resolveEvent($context),
                ApplicationLog::channel => $record->channel,
                ApplicationLog::level => strtolower($record->level->getName()),
                ApplicationLog::message => $this->truncateString((string)$record->message),
                ApplicationLog::context => $context,
                ApplicationLog::occurred_at => $record->datetime,
            ]);
        } catch (\Throwable) {
            // Logging darf den Hauptprozess nie unterbrechen.
            $exception = $record->context['exception'] ?? null;
            $message = sprintf(
                '[DatabaseLogHandler] Fehler beim Schreiben des Log-Eintrags in die Datenbank: %s%s',
                $record->message,
                PHP_EOL
            );

            if ($exception instanceof \Throwable) {
                $message .= sprintf(
                    '[DatabaseLogHandler] Ausnahme: %s in %s:%d%s',
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                    PHP_EOL
                );
            }

            $message .= sprintf('[DatabaseLogHandler] Context: %s%s', json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]', PHP_EOL);

            if (app()->runningInConsole()) {
                fwrite(STDERR, $message);
            } else {
                error_log($message);
            }
        }
    }

    /**
     * @param mixed $value
     * @param int $depth
     * @return mixed
     */
    private function sanitizeContext(mixed $value, int $depth): mixed
    {
        if ($depth >= self::MAX_CONTEXT_DEPTH) {
            return '[max_depth_reached]';
        }

        if (is_array($value)) {
            $sanitized = [];
            $count = 0;

            foreach ($value as $key => $item) {
                if ($count >= self::MAX_CONTEXT_ITEMS) {
                    $sanitized['__truncated__'] = 'max_items_reached';
                    break;
                }

                $normalizedKey = is_string($key) ? strtolower($key) : (string)$key;
                if (is_string($key) && $this->isSensitiveKey($normalizedKey)) {
                    $sanitized[$key] = '[redacted]';
                    $count++;
                    continue;
                }

                $sanitized[$key] = $this->sanitizeContext($item, $depth + 1);
                $count++;
            }

            return $sanitized;
        }

        if (is_object($value)) {
            if ($value instanceof \Throwable) {
                return [
                    'class' => $value::class,
                    'message' => $this->truncateString($value->getMessage()),
                    'code' => $value->getCode(),
                    'file' => $value->getFile(),
                    'line' => $value->getLine(),
                ];
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format(DATE_ATOM);
            }

            return sprintf('[object:%s]', $value::class);
        }

        if (is_string($value)) {
            return $this->truncateString($value);
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true)
            || str_contains($key, 'password')
            || str_contains($key, 'token')
            || str_contains($key, 'secret');
    }

    private function truncateString(string $value): string
    {
        if (mb_strlen($value) <= self::MAX_STRING_LENGTH) {
            return $value;
        }

        return mb_substr($value, 0, self::MAX_STRING_LENGTH) . '...[truncated]';
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveUserId(array $context): ?int
    {
        $userId = $context['user_id'] ?? Auth::id();

        if (is_numeric($userId)) {
            return (int)$userId;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveEvent(array $context): string
    {
        $event = $context['event'] ?? null;

        if (is_string($event) && trim($event) !== '') {
            return $event;
        }

        return 'application.log';
    }
}


<?php

namespace App\Logging;

use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger;

class DatabaseLoggerFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function __invoke(array $config): Logger
    {
        $configuredLevel = strtoupper((string)($config['level'] ?? 'INFO'));

        try {
            $level = Level::fromName($configuredLevel);
        } catch (\Throwable) {
            $level = Level::Info;
        }

        /** @var HandlerInterface[] $handlers */
        $handlers = [new DatabaseLogHandler($level)];

        return new Logger('database', $handlers);
    }
}


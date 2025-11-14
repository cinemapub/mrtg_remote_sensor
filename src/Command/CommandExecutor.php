<?php

declare(strict_types=1);

namespace MrtgSensor\Command;

use MrtgSensor\Cache\CacheAdapter;

final class CommandExecutor
{
    private const CACHE_CATEGORY = 'cli';

    public function __construct(
        private readonly CacheAdapter $cache,
        private readonly bool $debug = false,
    ) {}

    public function execute(
        string $command,
        ?string $workingDirectory = null,
        int $cacheSeconds = 30
    ): CommandResult {
        $fullCommand = $workingDirectory
            ? "cd \"{$workingDirectory}\"; {$command}"
            : $command;

        // Try cache first
        if ($cacheSeconds > 0) {
            $cached = $this->cache->getArray($fullCommand, self::CACHE_CATEGORY, $cacheSeconds);
            if ($cached !== null) {
                if ($this->debug) {
                    error_log("[CommandExecutor] Cache hit: {$fullCommand}");
                }

                return new CommandResult($cached);
            }
        }

        // Execute command
        $stdout = [];
        $exitCode = 0;
        exec($fullCommand, $stdout, $exitCode);

        if ($this->debug) {
            error_log("[CommandExecutor] Executed: {$fullCommand} (".count($stdout).' lines)');
        }

        // Cache result
        if ($cacheSeconds > 0 && $stdout) {
            $this->cache->setArray($fullCommand, self::CACHE_CATEGORY, $stdout, $cacheSeconds);
        }

        return new CommandResult($stdout, $exitCode);
    }
}

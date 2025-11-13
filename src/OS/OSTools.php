<?php

declare(strict_types=1);

namespace MrtgSensor\OS;

use MrtgSensor\Command\CommandExecutor;

abstract class OSTools
{
    public function __construct(
        protected readonly CommandExecutor $executor,
    ) {}

    abstract public function cpuload(): array;
    abstract public function cpuinfo(): array;
    abstract public function memusage(): array;
    abstract public function diskusage(string $path): array;
    abstract public function foldersize(string $path): array;
    abstract public function uptime(): string;
    abstract public function proccount(?string $filter): array;

    // Optional - not all OS support battery
    public function battery(): ?array
    {
        return null;
    }
}

<?php

declare(strict_types=1);

namespace MrtgSensor\Command;

final readonly class CommandResult
{
    public function __construct(
        public array $stdout,
        public int $exitCode = 0,
    ) {}

    public function isSuccess(): bool
    {
        return $this->exitCode === 0;
    }

    public function getFirstLine(): string
    {
        return $this->stdout[0] ?? '';
    }
}

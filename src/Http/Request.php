<?php

declare(strict_types=1);

namespace MrtgSensor\Http;

final readonly class Request
{
    private function __construct(
        public array $query,
        public array $server,
    ) {}

    public static function fromGlobals(): self
    {
        return new self($_GET, $_SERVER);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->query[$key]);
    }

    public function getServerName(): string
    {
        return $this->server['SERVER_NAME'] ?? gethostname();
    }

    public function getScriptName(): string
    {
        return $this->server['SCRIPT_NAME'] ?? '/index.php';
    }

    public function isHttps(): bool
    {
        return isset($this->server['HTTPS']) && $this->server['HTTPS'] === 'on';
    }
}

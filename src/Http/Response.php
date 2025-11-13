<?php

declare(strict_types=1);

namespace MrtgSensor\Http;

final class Response
{
    public function __construct(
        private readonly string $content,
        private readonly int $statusCode = 200,
        private readonly array $headers = [],
    ) {}

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }

    public static function text(string $content, int $statusCode = 200): self
    {
        return new self($content, $statusCode, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    public static function error(string $message, int $statusCode = 400): self
    {
        return self::text($message, $statusCode);
    }
}

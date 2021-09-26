<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\DSL;

final class Endpoint
{
    public function __construct(private string $method, private string $path)
    {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}

<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\DSL;

final class Endpoint
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly ?string $inputType,
        public readonly ?string $outputType
    ) {
    }
}

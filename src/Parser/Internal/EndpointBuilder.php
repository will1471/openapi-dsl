<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\Parser\Internal;

use Will1471\OpenApiDsl\DSL\Endpoint;

/**
 * @internal
 */
final class EndpointBuilder
{
    private ?string $method = null;
    private ?string $path = null;
    private ?string $inputType = null;
    private ?string $outputType = null;

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setInputType(string $inputType): void
    {
        $this->inputType = $inputType;
    }

    public function setOutputType(string $outputType): void
    {
        $this->outputType = $outputType;
    }

    public function build(): Endpoint
    {
        assert(is_string($this->method));
        assert(is_string($this->path));
        return new Endpoint($this->method, $this->path, $this->inputType, $this->outputType);
    }
}

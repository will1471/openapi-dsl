<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\Parser;

use Phplrt\Compiler\Compiler;
use Phplrt\Visitor\Traverser;
use Will1471\OpenApiDsl\Parser\Internal\Visitor;

final class Parser
{
    public function parse(string $content): ParseResult
    {
        $ast = (new Compiler())
            ->load(file_get_contents(__DIR__ . '/Internal/grammer'))
            ->parse($content);

        $visitor = new Visitor();

        /** @psalm-suppress InvalidArgument */
        (new Traverser())->with($visitor)->traverse($ast);

        return new ParseResult($visitor->objs, $visitor->enums, $visitor->endpoints);
    }
}

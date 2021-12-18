<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\Parser\ParseResult;

class ParserResultTest extends TestCase
{
    public function testObjNotFoundThrows(): void
    {
        $r = new ParseResult([], []);
        $this->expectException(\Exception::class);
        $r->getObj('Foo');
    }

    public function testEnumNotFoundThrows(): void
    {
        $r = new ParseResult([], []);
        $this->expectException(\Exception::class);
        $r->getEnum('Foo');
    }
}

<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\DSL\Obj;

class ObjTest extends TestCase
{
    public function testMissingPropThrows(): void
    {
        $obj = new Obj('name');
        $this->expectException(\Exception::class);
        $obj->getProp('foo');
    }
}

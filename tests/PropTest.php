<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\DSL\Prop;

class PropTest extends TestCase
{
    public function testGetters(): void
    {
        $prop = new Prop('name', 'type');
        $this->assertSame('name', $prop->name);
        $this->assertSame('type', $prop->type);
        $this->assertSame(false, $prop->isOptional);
        $this->assertSame(false, $prop->isNullable);
        $this->assertSame(false, $prop->isList);
    }
}

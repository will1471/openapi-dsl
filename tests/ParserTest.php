<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use Will1471\OpenApiDsl\Parser\Parser;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
    }

    private function parse(string $content)
    {
        $parser = new Parser();
        return $parser->parse($content);
    }

    public function testSimpleObj(): void
    {
        $data = <<<DATA
Foo
 - id: string
DATA;
        $result = $this->parse($data);
        $this->assertTrue($result->hasObj('Foo'));
        $this->assertFalse($result->hasObj('Bar'));
        $obj = $result->getObj('Foo');
        $this->assertTrue($obj->hasProp('id'));
        $this->assertFalse($obj->hasProp('qwerty'));
        $this->assertSame('string', $obj->getProp('id')->type);
    }

    public function testTwoSimpleObjects(): void
    {
        $data = <<<DATA
Foo
 - id: string
 
Bar
- id: int
DATA;
        $result = $this->parse($data);
        $this->assertTrue($result->hasObj('Foo'));
        $this->assertTrue($result->hasObj('Bar'));

        $foo = $result->getObj('Foo');
        $bar = $result->getObj('Bar');

        $this->assertSame('string', $foo->getProp('id')->type);
        $this->assertSame('int', $bar->getProp('id')->type);
    }

    public function testParseOptionalField(): void
    {
        $data = <<<DATA
Foo
- id?: string
- name: string
DATA;
        $r = $this->parse($data);
        $foo = $r->getObj('Foo');
        $id = $foo->getProp('id');
        $name = $foo->getProp('name');

        $this->assertSame('id', $id->name);
        $this->assertSame('string', $id->type);
        $this->assertSame(true, $id->isOptional);

        $this->assertSame('name', $name->name);
        $this->assertSame('string', $name->type);
        $this->assertSame(false, $name->isOptional);
    }

    public function testParseListType(): void
    {
        $data = <<<DATA
Foo
- single: string
- multi: string[]
DATA;
        $r = $this->parse($data);
        $foo = $r->getObj('Foo');
        $single = $foo->getProp('single');
        $multi = $foo->getProp('multi');

        $this->assertSame('string', $single->type);
        $this->assertSame(false, $single->isList);
        $this->assertSame('string', $multi->type);
        $this->assertSame(true, $multi->isList);
    }

    public function testParseOptionalType(): void
    {
        $data = <<<DATA
Foo
- opt_type: ?string
- req_type: string
DATA;
        $r = $this->parse($data);
        $this->assertSame('string', $r->getObj('Foo')->getProp('opt_type')->type);
        $this->assertSame('string', $r->getObj('Foo')->getProp('req_type')->type);
        $this->assertSame(true, $r->getObj('Foo')->getProp('opt_type')->isNullable);
        $this->assertSame(false, $r->getObj('Foo')->getProp('req_type')->isNullable);
    }

    public function testReservedWordForObjName(): void
    {
        $data = <<<DATA
class
 - name : string
DATA;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('class is a reserved word in PHP');
        $this->parse($data);
    }

    public function testReservedWordForPropName(): void
    {
        $data = <<<DATA
Foo
 - class : string
DATA;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('class is a reserved word in PHP');
        $this->parse($data);
    }

    public function testReservedWordForEnum(): void
    {
        $data = <<<DATA
enum class
 - MEMBER
DATA;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('class is a reserved word in PHP');
        $this->parse($data);
    }

    public function testReservedWordForEnumMember(): void
    {
        $data = <<<DATA
enum Foo
 - class
DATA;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('class is a reserved word in PHP');
        $this->parse($data);
    }

    public function testParserEnum(): void
    {
        $data = <<<DATA
enum Type
- ACTIVE
- DISABLED

Foo
- id: string
DATA;
        $r = $this->parse($data);
        $this->assertTrue($r->hasEnum('Type'));
        $this->assertFalse($r->hasObj('Type'));
        $this->assertFalse($r->hasEnum('Foo'));
        $this->assertTrue($r->hasObj('Foo'));
        $type = $r->getEnum('Type');
        $this->assertTrue($type->hasMember('ACTIVE'));
        $this->assertTrue($type->hasMember('DISABLED'));
        $this->assertFalse($type->hasMember('FOO'));
    }

    public function testParseEndpoint(): void
    {
        $data = <<<DATA
GET /foo
DATA;
        $r = $this->parse($data);
        $this->assertCount(1, $r->getEndpoints());
        $endpoint = $r->getEndpoints()[0];
        $this->assertSame('GET', $endpoint->method);
        $this->assertSame('/foo', $endpoint->path);

        $data = <<<DATA
DELETE /bar/baz?id={foo}
DATA;
        $r = $this->parse($data);
        $this->assertCount(1, $r->getEndpoints());
        $endpoint = $r->getEndpoints()[0];
        $this->assertSame('DELETE', $endpoint->method);
        $this->assertSame('/bar/baz', $endpoint->path);

        $data = <<<DATA
GET /foo/{id}/bar
DATA;
        $r = $this->parse($data);
        $this->assertCount(1, $r->getEndpoints());
        $endpoint = $r->getEndpoints()[0];
        $this->assertSame('GET', $endpoint->method);
        $this->assertSame('/foo/{id}/bar', $endpoint->path);
        $this->assertNull($endpoint->inputType);
        $this->assertNull($endpoint->outputType);
    }

    public function testParseEndpointInput(): void
    {
        $r = $this->parse(
            <<<DATA
GET /foo <= SomeObj
DATA
        );
        $this->assertCount(1, $r->getEndpoints());
        $endpoint = $r->getEndpoints()[0];
        $this->assertSame('SomeObj', $endpoint->inputType);
        $this->assertNull($endpoint->outputType);
    }

    public function testParseEndpointOutput(): void
    {
        $r = $this->parse(
            <<<DATA
GET /foo => SomeObj
DATA
        );
        $this->assertCount(1, $r->getEndpoints());
        $endpoint = $r->getEndpoints()[0];
        $this->assertNull($endpoint->inputType);
        $this->assertSame('SomeObj', $endpoint->outputType);
    }

    public function testParseEndpointInputAndOutput(): void
    {
        $r = $this->parse(
            <<<DATA
GET /foo <= InType => OutType
DATA
        );
        $this->assertCount(1, $r->getEndpoints());
        $endpoint = $r->getEndpoints()[0];
        $this->assertSame('InType', $endpoint->inputType);
        $this->assertSame('OutType', $endpoint->outputType);
    }

    public function testCanIgnoreComments(): void
    {
        $r = $this->parse(
            <<<DATA
# comment
Obj
- id: int
DATA
        );
        $this->assertCount(1, $r->objs());
        $r = $this->parse(
            <<<DATA
# comment
#Obj
#- id: int
DATA
        );
        $this->assertCount(0, $r->objs());
    }

    public function testParseQueryParams(): void
    {
        $r = $this->parse(
            <<<DATA
GET /search?q={q}
DATA
        );
        $this->assertCount(1, $r->getEndpoints());

        $r = $this->parse(
            <<<DATA
GET /search?q={q}&order={order}
DATA
        );
        $this->assertCount(1, $r->getEndpoints());

        $r = $this->parse(
            <<<DATA
GET /foo?ids[]={ids}
DATA
        );
        $this->assertCount(1, $r->getEndpoints());
    }
}

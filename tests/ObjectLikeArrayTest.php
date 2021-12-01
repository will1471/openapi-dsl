<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use Fp\Collections\NonEmptyArrayList;
use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\CodeGen\Internal\ObjectLikeArray;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\EnumMember;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;

class ObjectLikeArrayTest extends TestCase
{

    public function testSimple(): void
    {
        $type = function (Obj $obj): string {
            $result = new ParseResult([], [], []);
            $namespace = 'Will1471\\CodeGen';
            return (new ObjectLikeArray($obj, $namespace, $result))->toString();
        };

        $foo = new Obj('Foo');
        $foo->addProp(new Prop('id', 'int'));

        $this->assertSame('array{id: int}', $type($foo));

        $foo->addProp(new Prop('name', 'string'));
        $this->assertSame('array{id: int, name: string}', $type($foo));


        $foo->addProp(new Prop('desc', 'string', true));
        $this->assertSame('array{id: int, name: string, desc?: string}', $type($foo));

        $foo->addProp(new Prop('tags', 'string', false, true, true));

        $this->assertSame('array{id: int, name: string, desc?: string, tags: list<string>|null}', $type($foo));
    }

    public function testNested(): void
    {
        $foo = new Obj('Foo');
        $foo->addProp(new Prop('bar', 'Bar'));
        $bar = new Obj('Bar');
        $bar->addProp(new Prop('id', 'int'));

        $this->assertSame(
            'array{id: int}',
            (new ObjectLikeArray($bar, 'Will1471\\CodeGen', new ParseResult([$foo, $bar], [], [])))->toString()
        );
        $this->assertSame(
            'array{bar: array{id: int}}',
            (new ObjectLikeArray($foo, 'Will1471\\CodeGen', new ParseResult([$foo, $bar], [], [])))->toString()
        );
        $this->assertSame(
            'array{bar: Bar}',
            (new ObjectLikeArray($foo, 'Will1471\\CodeGen', new ParseResult([$bar, $foo], [], []), false))->toString()
        );
    }

    public function testEnum(): void
    {
        $msg = new Obj('Message');
        $msg->addProp(new Prop('id', 'string'));
        $msg->addProp(new Prop('status', 'StatusEnum'));
        $status = new Enum(
            'StatusEnum',
            NonEmptyArrayList::collectNonEmpty(
                [
                    new EnumMember('UNREAD'),
                    new EnumMember('UNACTIONED'),
                    new EnumMember('ACTIONED')
                ]
            )
        );
        $ns = 'Will1471\\CodeGen';
        $this->assertSame(
            "array{id: string, status: 'ACTIONED'|'UNACTIONED'|'UNREAD'}",
            (new ObjectLikeArray($msg, $ns, new ParseResult([$msg], [$status], [])))->toString()
        );
        $this->assertSame(
            "array{id: string, status: StatusEnum}",
            (new ObjectLikeArray($msg, $ns, new ParseResult([$msg], [$status], []), false))->toString()
        );
    }

    public function testListOfNestedObj(): void
    {
        $msg = new Obj('Message');
        $msg->addProp(new Prop('id', 'int'));
        $msg->addProp(new Prop('tags', 'Tag', false, true, false));
        $tag = new Obj('Tag');
        $tag->addProp(new Prop('text', 'string'));
        $ns = 'Will1471\\CodeGen';
        $this->assertSame(
            'array{id: int, tags: list<array{text: string}>}',
            (new ObjectLikeArray($msg, $ns, new ParseResult([$msg, $tag], [], [])))->toString()
        );
        $this->assertSame(
            'array{id: int, tags: list<Tag>}',
            (new ObjectLikeArray($msg, $ns, new ParseResult([$msg, $tag], [], []), false))->toString()
        );
    }
}

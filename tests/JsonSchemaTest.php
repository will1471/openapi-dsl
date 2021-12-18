<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use Fp\Collections\NonEmptyArrayList;
use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\CodeGen\JsonSchemaGenerator;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\EnumMember;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;

class JsonSchemaTest extends TestCase
{
    public function testObjectWithOptionalField(): void
    {
        $obj = new Obj('Foo');
        $obj->addProp(new Prop('id', 'integer', true));
        $obj->addProp(new Prop('name', 'string', false));
        $jsonSchema = (new JsonSchemaGenerator(new ParseResult([], [], [])))->buildObj($obj);
        $expected = [
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
            ]
        ];
        $this->assertSame($expected, $jsonSchema);
    }

    public function testNullableType(): void
    {
        $obj = new Obj('Foo');
        $obj->addProp(new Prop('nullable', 'string', false, false, true));
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['nullable'],
                'properties' => [
                    'nullable' => [
                        'oneOf' => [
                            ['type' => 'null'],
                            ['type' => 'string']
                        ]
                    ]
                ]
            ],
            (new JsonSchemaGenerator(new ParseResult([], [], [])))->buildObj($obj)
        );
    }

    public function testListOf(): void
    {
        $obj = new Obj('Foo');
        $obj->addProp(new Prop('strings', 'string', false, true, false));
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['strings'],
                'properties' => [
                    'strings' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string'
                        ]
                    ]
                ]
            ],
            (new JsonSchemaGenerator(new ParseResult([], [], [])))->buildObj($obj)
        );
    }

    public function testNullableListOfStrings(): void
    {
        $obj = new Obj('Foo');
        $obj->addProp(new Prop('strings', 'string', false, true, true));
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['strings'],
                'properties' => [
                    'strings' => [
                        'oneOf' => [
                            [
                                'type' => 'null'
                            ],
                            [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            (new JsonSchemaGenerator(new ParseResult([], [], [])))->buildObj($obj)
        );
    }

    public function testNested(): void
    {
        $parent = new Obj('Parent');
        $parent->addProp(new Prop('child', 'Child', false, false, false));
        $child = new Obj('Child');
        $child->addProp(new Prop('id', 'integer'));
        $child->addProp(new Prop('name', 'string'));
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['child'],
                'properties' => [
                    'child' => [
                        '$ref' => '#/definitions/Child'
                    ]
                ],
                'definitions' => [
                    'Child' => [
                        'type' => 'object',
                        'required' => ['id', 'name'],
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                        ]
                    ]
                ]
            ],
            (new JsonSchemaGenerator(new ParseResult([$child], [], [])))->build($parent)
        );
    }

    public function testThreeLevels(): void
    {
        $a = new Obj('A');
        $a->addProp(new Prop('b', 'B'));
        $b = new Obj('B');
        $b->addProp(new Prop('c', 'C'));
        $c = new Obj('C');
        $c->addProp(new Prop('id', 'integer'));
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['b'],
                'properties' => [
                    'b' => [
                        '$ref' => '#/definitions/B'
                    ]
                ],
                'definitions' => [
                    'B' => [
                        'type' => 'object',
                        'required' => ['c'],
                        'properties' => [
                            'c' => ['$ref' => '#/definitions/C'],
                        ]
                    ],
                    'C' => [
                        'type' => 'object',
                        'required' => ['id'],
                        'properties' => [
                            'id' => ['type' => 'integer'],
                        ]
                    ]
                ]
            ],
            (new JsonSchemaGenerator(new ParseResult([$b, $c], [], [])))->build($a)
        );
    }

    public function testEnum(): void
    {
        $enum = new Enum(
            'Status',
            NonEmptyArrayList::collectNonEmpty(
                [
                    new EnumMember('ACTIVE'),
                    new EnumMember('DISABLED')
                ]
            )
        );
        $this->assertSame(
            ['type' => 'string', 'enum' => ['ACTIVE', 'DISABLED']],
            (new JsonSchemaGenerator(new ParseResult([], [], [])))->buildEnum($enum)
        );
    }

    public function testNestedEnum(): void
    {
        $doc = new Obj('Document');
        $doc->addProp(new Prop('id', 'int'));
        $doc->addProp(new Prop('status', 'Status'));

        $enum = new Enum(
            'Status',
            NonEmptyArrayList::collectNonEmpty(
                [
                    new EnumMember('ACTIVE'),
                    new EnumMember('DISABLED')
                ]
            )
        );

        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['id', 'status'],
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'status' => ['$ref' => '#/definitions/Status']
                ],
                'definitions' => [
                    'Status' => [
                        'type' => 'string',
                        'enum' => ['ACTIVE', 'DISABLED']
                    ]
                ]
            ],
            (new JsonSchemaGenerator(new ParseResult([], [$enum], [])))->build($doc)
        );
    }
}

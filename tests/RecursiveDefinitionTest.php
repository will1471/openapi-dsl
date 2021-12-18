<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\CodeGen\JsonSchemaGenerator;
use Will1471\OpenApiDsl\Parser\Parser;

class RecursiveDefinitionTest extends TestCase
{
    public function testRecursiveJson(): void
    {
        $content = <<<DATA
Node
 - children: Node[]
DATA;
        $parser = new Parser();
        $parseResult = $parser->parse($content);
        $schema = (new JsonSchemaGenerator($parseResult))->buildRecursiveObj($parseResult->getObj('Node'));
        $this->assertSame(
            [
                '$ref' => '#/definitions/Node',
                'definitions' => [
                    'Node' => [
                        'type' => 'object',
                        'required' => ['children'],
                        'properties' => [
                            'children' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/definitions/Node']
                            ]
                        ]
                    ]
                ]
            ],
            $schema
        );
        file_put_contents(
            __DIR__ . '/../output/node.json',
            json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $content = <<<DATA
Thing
 - tree: Node

Node
 - children: Node[]
DATA;
        $parser = new Parser();
        $parseResult = $parser->parse($content);
        $schema = (new JsonSchemaGenerator($parseResult))->build($parseResult->getObj('Thing'));
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['tree'],
                'properties' => ['tree' => ['$ref' => '#/definitions/Node']],
                'definitions' => [
                    'Node' => [
                        'type' => 'object',
                        'required' => ['children'],
                        'properties' => [
                            'children' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/definitions/Node']
                            ]
                        ]
                    ]
                ]
            ],
            $schema
        );
        file_put_contents(
            __DIR__ . '/../output/node.json',
            json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}

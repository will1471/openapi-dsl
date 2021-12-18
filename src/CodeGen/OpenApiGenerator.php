<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen;

use cebe\openapi\spec\Components;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Paths;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Will1471\OpenApiDsl\DSL\Endpoint;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;

class OpenApiGenerator
{
    private JsonSchemaGenerator $schemaGenerator;

    public function __construct(
        private readonly string $title,
        private readonly string $version,
        private readonly ParseResult $parseResult
    ) {
        $this->schemaGenerator = new JsonSchemaGenerator($this->parseResult, '#/components/schemas/');
    }

    public function generate(): OpenApi
    {
        $openapi = new OpenApi(
            [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => $this->title,
                    'version' => $this->version,
                ],
                'paths' => [],
                'components' => [
                    'schemas' => []
                ]
            ]
        );

        foreach ($this->parseResult->endpoints as $endpoint) {
            assert($openapi->paths instanceof Paths);

            $pathItem = $this->getOrCreatePathItem($openapi, $endpoint);

            $content = [];
            if ($endpoint->outputType !== null) {
                $schema = $this->schemaGenerator->buildProp(new Prop('_', $endpoint->outputType));
                $content = ['content' => ['application/json' => ['schema' => $schema]]];
            }

            $requestBody = null;
            if ($endpoint->inputType !== null) {
                $schema = $this->schemaGenerator->buildProp(new Prop('_', $endpoint->inputType));
                $requestBody = new RequestBody(
                    [
                        'content' => [
                            'application/json' => [
                                'schema' => $schema
                            ]
                        ]
                    ]
                );
            }

            $pathItem->{strtolower($endpoint->method)} = new \cebe\openapi\spec\Operation(
                array_merge(
                    [
                        'responses' => ['200' => array_merge(['description' => 'Success'], $content)]
                    ],
                    !empty($requestBody) ? ['requestBody' => $requestBody] : []
                )
            );
        }

        while (($def = $this->schemaGenerator->defToBuild()) != null) {
            assert($openapi->components instanceof Components);
            $openapi->components->schemas = array_merge($openapi->components->schemas, [
                $def => new Schema(
                    $this->parseResult->hasEnum($def)
                        ? $this->schemaGenerator->buildEnum($this->parseResult->getEnum($def))
                        : $this->schemaGenerator->buildObj($this->parseResult->getObj($def))
                )
            ]);
        }

        return $openapi;
    }

    private function getOrCreatePathItem(OpenApi $openapi, Endpoint $endpoint): PathItem
    {
        assert($openapi->paths instanceof Paths);
        if ($openapi->paths->hasPath($endpoint->path)) {
            $pathItem = $openapi->paths->getPath($endpoint->path);
            assert($pathItem instanceof PathItem);
            return $pathItem;
        }

        $openapi->paths->addPath($endpoint->path, $pathItem = new PathItem([]));

        $matches = [];
        preg_match_all('!{([^}]+)}!', $endpoint->path, $matches);
        foreach ($matches[1] as $match) {
            $pathParam = new Parameter(
                [
                    'in' => 'path',
                    'name' => $match,
                    'required' => true,
                    'schema' => ['type' => 'string']
                ]
            );

            $pathItem->parameters = array_merge($pathItem->parameters, [$pathParam]);
        }

        return $pathItem;
    }
}

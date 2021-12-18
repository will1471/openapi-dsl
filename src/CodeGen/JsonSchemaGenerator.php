<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen;

use Fp\Collections\Entry;
use Will1471\OpenApiDsl\CodeGen\Internal\Scheduler;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;

final class JsonSchemaGenerator
{
    private Scheduler $definitions;

    public function __construct(
        private readonly ParseResult $parseResult,
        private readonly string $refPrefix = '#/definitions/'
    ) {
        $this->definitions = new Scheduler();
    }

    /**
     * @return array{
     *   '$ref':string,
     *   definitions:array<
     *     string,
     *     array{type:'object',required:list<string>,properties:array<string,array>}
     *     |array{type:'string',enum:list<string>}
     *   >
     * }
     */
    public function buildRecursiveObj(Obj $obj): array
    {
        $this->definitions->push($obj->name);
        $this->definitions->pop();

        $doc = [
            '$ref' => $this->refPrefix . $obj->name,
            'definitions' => [
                $obj->name => $this->buildObj($obj)
            ]
        ];

        foreach ($this->definitions() as $def) {
            $doc['definitions'][$def] = $this->parseResult->hasEnum($def)
                ? $this->buildEnum($this->parseResult->getEnum($def))
                : $this->buildObj($this->parseResult->getObj($def));
        }

        return $doc;
    }

    /**
     * @return array{
     *   type:'object',
     *   required:list<string>,
     *   properties:array<string,array>,
     *   definitions?:array<
     *     string,
     *     array{type:'object',required:list<string>,properties:array<string,array>}
     *     |array{type:'string',enum:list<string>}
     *   >
     * }
     */
    public function build(Obj $obj): array
    {
        $doc = $this->buildObj($obj);
        foreach ($this->definitions() as $def) {
            $doc['definitions'][$def] = $this->parseResult->hasEnum($def)
                ? $this->buildEnum($this->parseResult->getEnum($def))
                : $this->buildObj($this->parseResult->getObj($def));
        }
        return $doc;
    }

    /**
     * @return iterable<string>
     */
    public function definitions(): iterable
    {
        while ($def = $this->definitions->pop()) {
            yield $def;
        }
    }

    /**
     * @return array{type:'string',enum:list<string>}
     */
    public function buildEnum(Enum $enum): array
    {
        return [
            'type' => 'string',
            'enum' => $enum->members->keys()->toArray()
        ];
    }

    /**
     * @return array{type:'object',required:list<string>,properties:array<string,array>}
     */
    public function buildObj(Obj $obj): array
    {
        $properties = [];
        foreach ($obj->props()->values() as $prop) {
            $properties[$prop->name] = $this->buildProp($prop);
        }

        return [
            'type' => 'object',
            'required' => $obj->props()->filter(fn(Entry $e) => !$e->value->isOptional)->keys()->toArray(),
            'properties' => $properties
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function buildProp(Prop $prop): array
    {
        $type = ['type' => $prop->type == 'int' ? 'integer' : $prop->type];
        if ($this->parseResult->hasObj($prop->type) || $this->parseResult->hasEnum($prop->type)) {
            $this->definitions->push($prop->type);
            $type = ['$ref' => $this->refPrefix . $prop->type];
        }
        if ($prop->isList) {
            $type = ['type' => 'array', 'items' => $type];
        }
        if ($prop->isNullable) {
            $type = ['oneOf' => [['type' => 'null'], $type]];
        }
        return $type;
    }
}

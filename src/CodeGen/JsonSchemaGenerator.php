<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen;

use Fp\Collections\Entry;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;

final class JsonSchemaGenerator
{

    /**
     * @var array<string,bool>
     */
    private array $definitions = [];

    public function __construct(
        private readonly ParseResult $parseResult,
        private readonly string $refPrefix = '#/definitions/'
    ) {
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
        while (($def = $this->defToBuild()) !== null) {
            $doc['definitions'][$def] = $this->parseResult->hasEnum($def)
                ? $this->buildEnum($this->parseResult->getEnum($def))
                : $this->buildObj($this->parseResult->getObj($def));
        }
        return $doc;
    }

    public function defToBuild(): ?string
    {
        foreach ($this->definitions as $name => $done) {
            if ($done === false) {
                $this->definitions[$name] = true;
                return $name;
            }
        }
        return null;
    }

    /**
     * @return array{type:'string',enum:list<string>}
     */
    public function buildEnum(Enum $enum): array
    {
        return [
            'type' => 'string',
            'enum' => $enum->members
                ->keys()
                ->toArray()
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
            if (!isset($this->definitions[$prop->type])) {
                $this->definitions[$prop->type] = false;
            }
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

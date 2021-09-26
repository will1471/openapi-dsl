<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;

/**
 * @internal
 */
final class ObjectLikeArray
{

    public function __construct(
        private Obj $obj,
        private string $namespace,
        private ParseResult $pr,
        private bool $nestedObjectToArray = true
    ) {
    }

    private function fqn(Obj|Enum $thing): string
    {
        return '\\' . $this->namespace . '\\' . $thing->getName();
    }

    private function propType(Prop $prop): Union
    {
        $type = $prop->getType();
        if ($this->nestedObjectToArray) {
            $union = match (true) {
                $this->pr->hasObj($type) => new Union([$this->objToKeyedArray($this->pr->getObj($type))]),
                $this->pr->hasEnum($type) => UnionOfLiteralStrings::fromEnum($this->pr->getEnum($type)),
                $prop->getType() == 'string' => new Union([new TString()]),
                $prop->getType() == 'int', $prop->getType() == 'integer' => new Union([new TInt()]),
            };
        } else {
            $union = match (true) {
                $this->pr->hasObj($type) => new Union([new TNamedObject($this->fqn($this->pr->getObj($type)))]),
                $this->pr->hasEnum($type) => new Union([new TNamedObject($this->fqn($this->pr->getEnum($type)))]),
                $prop->getType() == 'string' => new Union([new TString()]),
                $prop->getType() == 'int', $prop->getType() == 'integer' => new Union([new TInt()]),
            };
        }
        if ($prop->isList()) {
            $union = new Union([new Atomic\TList($union)]);
        }
        if ($prop->isNullable()) {
            $union->addType(new Atomic\TNull());
        }
        if ($prop->isFieldOptional()) {
            $union->possibly_undefined = true;
        }
        return $union;
    }

    private function objToKeyedArray(Obj $obj): TKeyedArray
    {
        $types = [];
        foreach ($obj->props()->values() as $prop) {
            $types[$prop->getName()] = $this->propType($prop);
        }
        assert(!empty($types));
        return new TKeyedArray($types);
    }

    public function toString(): string
    {
        return $this->objToKeyedArray($this->obj)
            ->toNamespacedString(
                $this->namespace,
                [],
                $this->fqn($this->obj),
                false
            );
    }
}

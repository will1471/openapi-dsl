<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\Parser;

use Fp\Collections\ArrayList;
use Fp\Collections\HashMap;
use Will1471\OpenApiDsl\DSL\Endpoint;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\Obj;

final class ParseResult
{
    /**
     * @var HashMap<string, Obj>
     */
    public readonly HashMap $objs;

    /**
     * @var HashMap<string, Enum>
     */
    public readonly HashMap $enums;

    /**
     * @param list<Obj> $objs
     * @param list<Enum> $enums
     * @param list<Endpoint> $endpoints
     */
    public function __construct(array $objs, array $enums, public readonly array $endpoints = [])
    {
        $this->objs = ArrayList::collect($objs)->toHashMap(fn(Obj $i) => [$i->name, $i]);
        $this->enums = ArrayList::collect($enums)->toHashMap(fn(Enum $i) => [$i->name, $i]);
    }

    public function hasObj(string $name): bool
    {
        return $this->objs->get($name)->isSome();
    }

    public function getObj(string $name): Obj
    {
        return $this->objs->get($name)->getOrCall(fn() => throw new \Exception('obj not found.'));
    }

    public function hasEnum(string $name): bool
    {
        return $this->enums->get($name)->isSome();
    }

    public function getEnum(string $name): Enum
    {
        return $this->enums->get($name)->getOrCall(fn() => throw new \Exception('enum not found'));
    }
}

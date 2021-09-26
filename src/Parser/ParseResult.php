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
    private HashMap $objs;

    /**
     * @var HashMap<string, Enum>
     */
    private HashMap $enums;

    /**
     * @param Obj[] $objs
     * @param Enum[] $enums
     * @param Endpoint[] $endpoints
     */
    public function __construct(array $objs, array $enums, private array $endpoints = [])
    {
        $this->objs = ArrayList::collect($objs)->toHashMap(fn($i) => [$i->getName(), $i]);
        $this->enums = ArrayList::collect($enums)->toHashMap(fn($i) => [$i->getName(), $i]);
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

    /**
     * @return Endpoint[]
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * @return HashMap<string, Obj>
     */
    public function objs(): HashMap
    {
        return $this->objs;
    }

    /**
     * @return HashMap<string,Enum>
     */
    public function enums(): HashMap
    {
        return $this->enums;
    }
}

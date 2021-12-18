<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\DSL;

use Fp\Collections\HashMap;
use Will1471\OpenApiDsl\ReservedWord;

final class Obj
{
    /**
     * @var array<string,Prop>
     */
    private array $props = [];

    public function __construct(public string $name)
    {
        ReservedWord::check($this->name);
    }

    /**
     * @return HashMap<string,Prop>
     */
    public function props(): HashMap
    {
        return HashMap::collect($this->props);
    }

    public function addProp(Prop $prop): void
    {
        $this->props[$prop->name] = $prop;
    }

    public function hasProp(string $name): bool
    {
        return isset($this->props[$name]);
    }

    public function getProp(string $name): Prop
    {
        return $this->props[$name] ?? throw new \Exception('no prop with that name');
    }

    /**
     * @return list<Prop>
     */
    public function optionalProp(): array
    {
        return $this->props()->values()->filter(fn(Prop $p): bool => $p->isOptional)->toArray();
    }

    /**
     * @return list<Prop>
     */
    public function requiredProp()
    {
        return $this->props()->values()->filter(fn(Prop $p): bool => !$p->isOptional)->toArray();
    }
}

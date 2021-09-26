<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\DSL;

use Fp\Collections\NonEmptyArrayList;
use Fp\Collections\NonEmptyHashMap;
use Will1471\OpenApiDsl\ReservedWord;

final class Enum
{

    /**
     * @var NonEmptyHashMap<string,EnumMember>
     */
    private NonEmptyHashMap $members;

    /**
     * @param string $name
     * @param NonEmptyArrayList<EnumMember> $members
     * @throws \Exception
     */
    public function __construct(private string $name, NonEmptyArrayList $members)
    {
        ReservedWord::check($this->name);
        $this->members = $members->toNonEmptyHashMap(fn(EnumMember $m): array => [$m->getName(), $m]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasMember(string $name): bool
    {
        return $this->members->get($name)->isSome();
    }

    /**
     * @return NonEmptyHashMap<string,EnumMember>
     */
    public function members(): NonEmptyHashMap
    {
        return $this->members;
    }
}

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
    public readonly NonEmptyHashMap $members;

    /**
     * @param NonEmptyArrayList<EnumMember> $members
     * @throws \Exception
     */
    public function __construct(public readonly string $name, NonEmptyArrayList $members)
    {
        ReservedWord::check($this->name);
        $this->members = $members->toNonEmptyHashMap(fn(EnumMember $m): array => [$m->name, $m]);
    }

    public function hasMember(string $name): bool
    {
        return $this->members->get($name)->isSome();
    }
}

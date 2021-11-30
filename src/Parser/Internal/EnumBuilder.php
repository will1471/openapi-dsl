<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\Parser\Internal;

use Fp\Collections\NonEmptyArrayList;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\EnumMember;

final class EnumBuilder
{

    /**
     * @var list<EnumMember>
     */
    private array $members = [];

    public function __construct(private readonly string $name)
    {
    }

    public function addMember(EnumMember $member): void
    {
        $this->members[] = $member;
    }

    public function build(): Enum
    {
        assert(!empty($this->members));
        return new Enum($this->name, NonEmptyArrayList::collectNonEmpty($this->members));
    }
}

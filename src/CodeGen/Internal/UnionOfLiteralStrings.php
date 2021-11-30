<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;
use Will1471\OpenApiDsl\DSL\Enum;

/**
 * @internal
 */
final class UnionOfLiteralStrings
{
    public static function fromEnum(Enum $enum): Union
    {
        return new Union(
            $enum->members
                ->keys()
                ->map(fn(string $name): TLiteralString => new TLiteralString($name))
                ->toArray()
        );
    }
}

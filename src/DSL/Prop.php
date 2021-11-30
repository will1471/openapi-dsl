<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\DSL;

use Will1471\OpenApiDsl\ReservedWord;

final class Prop
{

    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isOptional = false,
        public readonly bool $isList = false,
        public readonly bool $isNullable = false
    ) {
        ReservedWord::check($this->name);
    }
}

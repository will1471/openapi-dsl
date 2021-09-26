<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\DSL;

use Will1471\OpenApiDsl\ReservedWord;

final class EnumMember
{
    public function __construct(private string $name)
    {
        ReservedWord::check($this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}

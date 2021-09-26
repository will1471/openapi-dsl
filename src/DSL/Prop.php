<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\DSL;

use Will1471\OpenApiDsl\ReservedWord;

final class Prop
{

    public function __construct(
        private string $name,
        private string $type,
        private bool $optional = false,
        private bool $list = false,
        private bool $nullable = false
    ) {
        ReservedWord::check($this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isFieldOptional(): bool
    {
        return $this->optional;
    }

    public function isList(): bool
    {
        return $this->list;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}

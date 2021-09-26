<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\Parser\Internal;

use Will1471\OpenApiDsl\DSL\Prop;

/**
 * @internal
 */
final class PropBuilder
{

    private ?string $name = null;
    private ?string $type = null;
    private bool $optional = false;
    private bool $nullable = false;
    private bool $list = false;

    public function setFieldName(string $val): void
    {
        $this->name = $val;
    }

    public function setFieldOptional(bool $val): void
    {
        $this->optional = $val;
    }

    public function setTypeName(string $val): void
    {
        $this->type = $val;
    }

    public function setTypeNullable(bool $val): void
    {
        $this->nullable = $val;
    }

    public function setTypeIsList(bool $val): void
    {
        $this->list = $val;
    }

    public function build(): Prop
    {
        assert(is_string($this->name));
        assert(is_string($this->type));
        return new Prop($this->name, $this->type, $this->optional, $this->list, $this->nullable);
    }
}

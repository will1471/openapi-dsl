<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use Psalm\Type\Atomic;
use Psalm\Type\Union;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;

/**
 * @internal
 */
final class Type
{
    private readonly Atomic | Union $type;
    private int $phpMajorVersion = 7;
    private int $phpMinorVersion = 4;

    public function __construct(
        private readonly Obj $obj,
        private readonly Prop $prop,
        private readonly string $namespace,
        private readonly bool $optionalAsNull = true
    ) {
        $this->type = $this->type($this->prop);
    }

    public function type(?Prop $prop = null): Atomic|Union
    {
        $prop = $prop ?? $this->prop;
        if ($prop->type == 'string') {
            $type = new Atomic\TString();
        }
        if ($prop->type == 'int' || $prop->type == 'integer') {
            $type = new Atomic\TInt();
        }
        if (!isset($type) || !$type instanceof Atomic) {
            $type = new Atomic\TNamedObject('\\' . $this->namespace . '\\' . $prop->type);
        }
        if ($prop->isList) {
            $type = new Atomic\TList(new Union([$type]));
        }
        if ($prop->isNullable || ($prop->isOptional && $this->optionalAsNull)) {
            $type = new Union(
                [
                    new Atomic\TNull(),
                    $type
                ]
            );
        }
        return $type;
    }

    public function psalm(): string
    {
        return $this->type->toNamespacedString(
            $this->namespace,
            [],
            '\\' . $this->namespace . '\\' . $this->obj->name,
            false
        );
    }

    public function docbloc(): string
    {
        return $this->type->toNamespacedString(
            $this->namespace,
            [],
            '\\' . $this->namespace . '\\' . $this->obj->name,
            true
        );
    }

    public function php(): string
    {
        return $this->type->toPhpString(
            '\\' . $this->namespace,
            [],
            '\\' . $this->namespace . '\\' . $this->obj->name,
            $this->phpMajorVersion,
            $this->phpMinorVersion
        ) ?? '';
    }

    public function fullyExpressedInPhp(): bool
    {
        return $this->type->canBeFullyExpressedInPhp($this->phpMajorVersion, $this->phpMinorVersion);
    }
}

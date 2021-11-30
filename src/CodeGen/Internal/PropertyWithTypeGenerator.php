<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use Laminas\Code\Generator\PropertyGenerator;

/**
 * Extends Laminas PropertyGenerator to support property types
 * @internal
 */
final class PropertyWithTypeGenerator extends PropertyGenerator
{

    private ?string $type = null;

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function generate(): string
    {
        $output = '';

        if (($docBlock = $this->getDocBlock()) !== null) {
            $docBlock->setIndentation('    ');
            $output .= $docBlock->generate();
        }
        return $output . ($this->indentation
            . $this->getVisibility()
            . ($this->isStatic() ? ' static' : '')
            . ($this->type ? ' ' . $this->type : '')
            . ' $' . $this->getName() . ';');
    }
}

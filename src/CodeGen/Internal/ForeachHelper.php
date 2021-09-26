<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;

/**
 * @internal
 */
final class ForeachHelper
{
    public function __construct(private Expr $expr)
    {
    }

    public function as_(Variable $var): ForeachHelperWithAs
    {
        return new ForeachHelperWithAs($this->expr, $var);
    }
}

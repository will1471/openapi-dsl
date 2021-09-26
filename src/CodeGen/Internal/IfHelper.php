<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;

/**
 * @internal
 */
final class IfHelper
{

    private array $then = [];
    private ?Stmt\Else_ $else = null;

    public function __construct(private Expr $if)
    {
    }

    public function then(Stmt ...$expressions): self
    {
        $this->then = $expressions;
        return $this;
    }

    public function else(Expression|Stmt ...$expressions): self
    {
        $this->else = new Stmt\Else_($expressions);
        return $this;
    }

    public function build(): If_
    {
        return new If_(
            $this->if,
            [
                'stmts' => $this->then,
                'elseifs' => [],
                'else' => $this->else
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use PhpParser\Comment\Doc;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Foreach_;

/**
 * @internal
 */
final class ForeachHelperWithAs
{
    private bool $withMixedDocBloc = false;

    public function __construct(private readonly Expr $expr, private readonly Variable $as)
    {
    }

    public function withMixedDocBloc(): self
    {
        $this->withMixedDocBloc = true;
        return $this;
    }

    public function exec(Stmt ...$stmts): Stmt\Foreach_
    {
        $attr = [];
        if ($this->withMixedDocBloc) {
            assert(is_string($this->as->name));
            $attr['comments'] = [new Doc("/** @psalm-var mixed \${$this->as->name} */")];
        }
        return new Foreach_(
            $this->expr,
            $this->as,
            [
                'keyVar' => null,
                'byRef' => false,
                'stmts' => $stmts
            ],
            $attr
        );
    }
}

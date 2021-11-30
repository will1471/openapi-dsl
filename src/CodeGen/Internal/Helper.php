<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\PrettyPrinter\Standard;

/**
 * @internal
 */
abstract class Helper
{
    public static function if_(Expr $expr): IfHelper
    {
        return new IfHelper($expr);
    }

    public static function var_(string $name): Variable
    {
        return new Variable($name);
    }

    public static function array_key_exists(string $dimName, Variable $variable): FuncCall
    {
        return self::function('array_key_exists')(new String_($dimName), $variable);
    }

    public static function assign(Expr $l, Expr $r): Expression
    {
        return new Expression(new Assign($l, $r));
    }

    public static function array_dim(Variable $var, string $dimName): ArrayDimFetch
    {
        return new ArrayDimFetch($var, new String_($dimName));
    }

    public static function empty_array(): Array_
    {
        return new Array_();
    }

    public static function null_(): ConstFetch
    {
        return new ConstFetch(new Name('null'));
    }

    public static function true_(): ConstFetch
    {
        return new ConstFetch(new Name('true'));
    }

    public static function return_(Expr $expr): Return_
    {
        return new Return_($expr);
    }

    public static function array_map(Expr $callable, Expr $array): FuncCall
    {
        return self::function('array_map')($callable, $array);
    }

    /**
     * @param \PhpParser\Node[] $stmts
     */
    public static function to_string(array $stmts): string
    {
        return (new Standard(['shortArraySyntax' => true]))->prettyPrint($stmts);
    }

    public static function isset_(Expr $expr): Isset_
    {
        return new Isset_([$expr]);
    }

    /**
     * @return callable(Expr...):FuncCall
     */
    public static function function(string $functionName): callable
    {
        return function (Expr ...$exprs) use ($functionName): FuncCall {
            return new FuncCall(
                new Name($functionName),
                array_map(fn(Expr $expr) => new Arg($expr), $exprs)
            );
        };
    }

    public static function foreach_(Expr $expr): ForeachHelper
    {
        return new ForeachHelper($expr);
    }

    public static function append(Variable $var, Expr $expr): Expression
    {
        return new Expression(new Assign(new ArrayDimFetch($var), $expr));
    }

    public static function not_(Expr $expr): Expr\BooleanNot
    {
        return new Expr\BooleanNot($expr);
    }

    public static function throwException(string|Expr $message = ''): Throw_
    {
        return new Throw_(
            new New_(
                new FullyQualified('Exception'),
                [new Arg($message instanceof Expr ? $message : new String_($message))]
            )
        );
    }

    /**
     * @return callable(Expr...):StaticCall
     */
    public static function callStaticMethod(string $class, string $method): callable
    {
        return function (Expr ...$exprs) use ($class, $method): StaticCall {
            return new StaticCall(
                new Name($class),
                new Identifier($method),
                array_map(fn(Expr $expr) => new Arg($expr), $exprs)
            );
        };
    }

    public static function concat(Expr $l, Expr $r): Concat
    {
        return new Concat($l, $r);
    }

    public static function str(string $string): String_
    {
        return new String_($string);
    }
}

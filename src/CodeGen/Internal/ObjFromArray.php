<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;
use Will1471\OpenApiDsl\CodeGen\Internal\Helper as c;

/**
 * @internal
 */
final class ObjFromArray
{
    public function __construct(
        private readonly Obj $obj,
        private readonly ParseResult $parseResult,
        private readonly string $ns
    ) {
    }

    public function generate(): MethodGenerator
    {
        $fqn = '\\' . $this->ns . '\\' . $this->obj->name;
        $arrayType = (new ObjectLikeArray($this->obj, $this->ns, $this->parseResult))->toString();
        $tags = [
            new ParamTag('array', ['array']),
            new GenericTag('psalm-param', $arrayType . ' $array'),
            new ReturnTag([$this->obj->name])
        ];
        $method = new MethodGenerator(
            'fromArray',
            [new ParameterGenerator('array', 'array')],
            MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
            $this->code(),
            new DocBlockGenerator(null, null, $tags)
        );
        $method->setReturnType($fqn);
        return $method;
    }

    private function code(): string
    {
        $inputArray = c::var_('array');

        $opt = $this->obj->optionalProp();
        $extra = count($opt) > 0 ? c::var_('extra') : null;
        $stmts = [];

        if ($extra) {
            $stmts[] = c::assign($extra, c::empty_array());
            foreach ($opt as $prop) {
                $assignDest = c::array_dim($extra, $prop->name);
                $assignValue = $this->extractExpr($prop, $inputArray);

                $stmts[] = c::if_(c::array_key_exists($prop->name, $inputArray))
                    ->then(c::assign($assignDest, $assignValue))
                    ->build();
            }
        }

        $args = [];
        foreach ($this->obj->requiredProp() as $prop) {
            $args[] = new Arg($this->extractExpr($prop, $inputArray));
        }
        if ($extra) {
            $args[] = new Arg($extra);
        }

        $stmts[] = c::return_(new New_(new Name($this->obj->name), $args));

        return c::to_string($stmts);
    }

    private function extractExpr(Prop $prop, Variable $inputArray): Expr
    {
        $name = $prop->name;
        $type = $prop->type;
        $isObj = $this->parseResult->hasObj($prop->type);
        $isEnum = $this->parseResult->hasEnum($prop->type);

        $expr = c::array_dim($inputArray, $name);

        if ($isObj || $isEnum) {
            $method = $isObj ? 'fromArray' : 'fromString';
            if ($prop->isList) {
                $callable = new Array_(
                    [
                        new ArrayItem(new Expr\ClassConstFetch(new Name($type), new Identifier('class'))),
                        new ArrayItem(new String_($method))
                    ],
                );
                $expr = c::array_map($callable, $expr);
            } else {
                $expr = c::callStaticMethod($type, $method)($expr);
            }
        }
        if ($prop->isNullable) {
            $expr = new Expr\Ternary(
                c::isset_(c::array_dim(c::var_('array'), $name)),
                $expr,
                c::null_()
            );
        }

        return $expr;
    }
}

<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;
use Will1471\OpenApiDsl\CodeGen\Internal\Helper as c;

/**
 * @internal
 */
final class ObjFromUntypedArray
{

    public function __construct(
        private readonly Obj $obj,
        private readonly ParseResult $parseResult,
        private readonly string $namespace
    ) {
    }

    public function generate(): MethodGenerator
    {
        $psalmType = (new ObjectLikeArray($this->obj, $this->namespace, $this->parseResult))->toString();
        $method = new MethodGenerator(
            'fromUntypedArray',
            [new ParameterGenerator('array', 'array')],
            MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
            $this->body(),
            new DocBlockGenerator(
                null,
                null,
                [
                    new ParamTag('array', ['array']),
                    new ReturnTag('array'),
                    new GenericTag('psalm-return', $psalmType)
                ]
            )
        );
        $method->setReturnType('array');
        return $method;
    }

    private function body(): string
    {
        $inputArray = c::var_('array');

        $stmts = [];

        $stmts[] = c::assign($r = c::var_('r'), c::empty_array());

        foreach ($this->obj->props()->values() as $prop) {
            foreach ($this->propertyStatements($inputArray, $prop, $r) as $stmt) {
                $stmts[] = $stmt;
            }
        }

        $stmts[] = c::return_($r);

        return c::to_string($stmts);
    }

    /**
     * @return Node[]
     */
    private function propertyStatements(Variable $inputArray, Prop $prop, Variable $outputArray): array
    {
        $stmts = [];

        $arrayKeyExists = c::array_key_exists($prop->name, $inputArray);

        if (!$prop->isOptional) {
            $stmts[] = c::if_(c::not_($arrayKeyExists))
                ->then(c::throwException('Property "' . $prop->name . '" is required, but missing.'))
                ->build();
        }

        // @todo can remove?
        if (!$prop->isNullable) {
            $stmts[] = c::if_(c::not_(c::isset_(c::array_dim($inputArray, $prop->name))))
                ->then(c::throwException('Property "' . $prop->name . '" is not nullable, but found null.'))
                ->build();
        }

        if ($prop->isList == false) {
            $stmts[] = $this->assertInputArrayType($inputArray, $prop);
            $stmts[] = $this->assignProp($inputArray, $prop, $outputArray);
        } else {
            $stmts[] = $this->assertFunc($inputArray, 'is_array', $prop);
            $stmts[] = c::assign($tmp = c::var_('tmp'), c::empty_array());
            $stmts[] = $this->foreachAssertAndAssignPropToTmp($inputArray, $prop, $tmp);
            $stmts[] = c::assign(c::array_dim($outputArray, $prop->name), $tmp);
        }

        if ($prop->isOptional) {
            // if the field is optional, only do the above if the field is set
            return [
                c::if_($arrayKeyExists)->then(...$stmts)->build()
            ];
        }

        return $stmts;
    }

    /**
     * @return 'int'|'string'|'enum'|'obj'
     */
    private function propType(Prop $prop): string
    {
        return match (true) {
            $prop->type == 'int' || $prop->type == 'integer' => 'int',
            $prop->type == 'string' => 'string',
            $this->parseResult->hasEnum($prop->type) => 'enum',
            $this->parseResult->hasObj($prop->type) => 'obj',
            default => throw new \LogicException()
        };
    }

    private function assertInputArrayType(Variable $inputArray, Prop $prop): If_
    {
        return match ($this->propType($prop)) {
            'int' => $this->assertFunc($inputArray, 'is_int', $prop),
            'string', 'enum' => $this->assertFunc($inputArray, 'is_string', $prop),
            'obj' => $this->assertFunc($inputArray, 'is_array', $prop),
        };
    }

    /**
     * Takes data from untyped input and assigns it to clean return value, assumes the type of input has already
     * been asserted.
     *
     * If the property type is a enum or obj, delegate to their fromUntyped method
     */
    private function assignProp(Variable $inputArray, Prop $prop, Variable $outputArray): Expression
    {
        $value = c::array_dim($inputArray, $prop->name);

        $isObj = $this->parseResult->hasObj($prop->type);
        $isEnum = $this->parseResult->hasEnum($prop->type);
        if ($isObj || $isEnum) {
            $value = c::callStaticMethod($prop->type, $isObj ? 'fromUntypedArray' : 'checkString')($value);
        }

        return c::assign(c::array_dim($outputArray, $prop->name), $value);
    }

    private function foreachAssertAndAssignPropToTmp(Variable $inputArray, Prop $prop, Variable $tmp): Foreach_
    {
        $propType = $this->propType($prop);

        $funcName = match ($propType) {
            'int' => 'is_int',
            'string', 'enum' => 'is_string',
            'obj' => 'is_array'
        };

        $item = c::var_('item');

        $assign = $item;

        if ($propType == 'enum' || $propType == 'obj') {
            $method = $propType == 'enum' ? 'checkString' : 'fromUntypedArray';
            $assign = c::callStaticMethod($prop->type, $method)($item);
        }

        return c::foreach_(c::array_dim($inputArray, $prop->name))
            ->as_($item)
            ->withMixedDocBloc()
            ->exec(
                c::if_(c::function($funcName)($item))
                    ->then(c::append($tmp, $assign))
                    ->else(c::throwException())
                    ->build()
            );
    }

    /**
     * @param 'is_array'|'is_int'|'is_string' $funcName
     */
    private function assertFunc(Variable $inputArray, string $funcName, Prop $prop): If_
    {
        $expectedType = substr($funcName, 3);
        return c::if_(c::not_(c::function($funcName)(c::array_dim($inputArray, $prop->name))))
            ->then(
                c::throwException(
                    c::concat(
                        c::str("Property {$prop->name} should be $expectedType, but found"),
                        c::function('get_debug_type')(c::array_dim($inputArray, $prop->name))
                    )
                )
            )->build();
    }
}

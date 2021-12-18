<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use Will1471\OpenApiDsl\CodeGen\Internal\PropertyWithTypeGenerator;
use Will1471\OpenApiDsl\CodeGen\Internal\UnionOfLiteralStrings;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\CodeGen\Internal\Helper as c;
use Will1471\OpenApiDsl\DSL\EnumMember;

final class EnumGenerator
{
    private string $fqn;

    public function __construct(private readonly Enum $enum, private readonly string $namespace)
    {
        $this->fqn = '\\' . $this->namespace . '\\' . $this->enum->name;
    }

    public function generate(): string
    {
        $stringUnionType = UnionOfLiteralStrings::fromEnum($this->enum)
            ->toNamespacedString($this->namespace, [], $this->enum->name, false);

        $class = new ClassGenerator(
            $this->enum->name,
            $this->namespace,
            ClassGenerator::FLAG_FINAL,
            null,
            [],
            [
                $this->valueProp($stringUnionType),
            ],
            [
                $this->constructor($stringUnionType),
                $this->eq(),
                $this->fromString($stringUnionType),
                $this->checkString($stringUnionType),
                ...$this->instanceMethods()
            ],
            null
        );

        return $class->generate();
    }

    private function valueProp(string $stringUnionType): PropertyWithTypeGenerator
    {
        $prop = new PropertyWithTypeGenerator('value');
        $prop->setType('string');
        $prop->setDocBlock(new DocBlockGenerator(null, null, [new GenericTag('psalm-var', $stringUnionType)]));
        return $prop;
    }

    /**
     * @return list<MethodGenerator>
     */
    private function instanceMethods(): array
    {
        return $this->enum->members->values()->map(
            function (EnumMember $member): MethodGenerator {
                $m = new MethodGenerator(
                    $member->name,
                    [],
                    MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
                    'return new self(\'' . $member->name . '\');'
                );
                $m->setReturnType($this->fqn);
                return $m;
            }
        )->toArray();
    }

    private function constructor(string $stringUnionType): MethodGenerator
    {
        return new MethodGenerator(
            '__construct',
            [new ParameterGenerator('string', 'string')],
            MethodGenerator::FLAG_PRIVATE,
            '$this->value = $string;',
            new DocBlockGenerator(
                null,
                null,
                [
                    new GenericTag('psalm-param', $stringUnionType . ' $string')
                ]
            )
        );
    }

    private function eq(): MethodGenerator
    {
        $method = new MethodGenerator(
            'eq',
            [new ParameterGenerator('other', $this->fqn)],
            MethodGenerator::FLAG_PUBLIC,
            'return $this->value === $other->value;'
        );
        $method->setReturnType('bool');
        return $method;
    }

    private function fromString(string $stringUnionType): MethodGenerator
    {
        $method = new MethodGenerator(
            'fromString',
            [new ParameterGenerator('string', 'string')],
            MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
            'return new self($string);',
            new DocBlockGenerator(
                null,
                null,
                [
                    new GenericTag('psalm-param', $stringUnionType . ' $string')
                ]
            )
        );
        $method->setReturnType($this->fqn);
        return $method;
    }

    private function checkString(string $stringUnionType): MethodGenerator
    {
        $strings = new Array_(
            $this->enum->members
                ->keys()
                ->map(fn(string $s) => new String_($s))
                ->map(fn(String_ $s) => new ArrayItem($s))
                ->toArray()
        );

        $string = c::var_('string');

        $body = c::to_string(
            [
                c::if_(c::not_(c::function('in_array')($string, $strings, c::true_())))
                    ->then(c::throwException())
                    ->build(),
                c::return_($string)
            ]
        );

        $method = new MethodGenerator(
            'checkString',
            [new ParameterGenerator('string', 'string')],
            MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
            $body,
            new DocBlockGenerator(null, null, [new GenericTag('psalm-return', $stringUnionType . ' $string')])
        );
        $method->setReturnType('string');
        return $method;
    }
}

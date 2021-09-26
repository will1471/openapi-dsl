<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\VarTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Will1471\OpenApiDsl\CodeGen\Internal\ObjectLikeArray;
use Will1471\OpenApiDsl\CodeGen\Internal\ObjFromArray;
use Will1471\OpenApiDsl\CodeGen\Internal\ObjFromUntypedArray;
use Will1471\OpenApiDsl\CodeGen\Internal\PropertyWithTypeGenerator;
use Will1471\OpenApiDsl\CodeGen\Internal\Type;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\DSL\Prop;
use Will1471\OpenApiDsl\Parser\ParseResult;

final class ObjGenerator
{
    public function __construct(
        private Obj $obj,
        private ParseResult $parseResult,
        private string $namespace = 'Will1471\\CodeGen',
    ) {
    }

    public function generate(): string
    {
        $properties = [];

        $methods = [
            $this->constructorGenerator(),
            (new ObjFromArray($this->obj, $this->parseResult, $this->namespace))->generate(),
            (new ObjFromUntypedArray($this->obj, $this->parseResult, $this->namespace))->generate(),
        ];

        foreach ($this->obj->requiredProp() as $prop) {
            $properties[] = $this->propertyGenerator($prop);
            $methods[] = $this->getterGenerator($prop);
        }

        $haveExtra = false;
        foreach ($this->obj->optionalProp() as $prop) {
            $haveExtra = true;
            $methods[] = $this->hasOptionalField($prop);
            $methods[] = $this->getOptionalField($prop);
        }

        if ($haveExtra) {
            $properties[] = $this->extraProperty();
        }

        $class = new ClassGenerator(
            $this->obj->name,
            $this->namespace,
            ClassGenerator::FLAG_FINAL,
            null,
            [],
            $properties,
            $methods,
            null
        );

        return $class->generate();
    }

    private function propertyGenerator(Prop $prop): PropertyWithTypeGenerator
    {
        $type = new Type($this->obj, $prop, $this->namespace);

        $property = new PropertyWithTypeGenerator(
            $prop->getName(),
            null,
            PropertyWithTypeGenerator::FLAG_PRIVATE
        );
        $property->omitDefaultValue(true);
        $property->setType($type->php());

        if (!$type->fullyExpressedInPhp()) {
            $docType = $type->docbloc();
            $psalmType = $type->psalm();
            $tags = [new VarTag(null, $docType)];
            if ($psalmType != $docType) {
                $tags[] = new GenericTag('psalm-var', $psalmType);
            }
            $property->setDocBlock(new DocBlockGenerator(null, null, $tags));
        }

        return $property;
    }

    private function getterGenerator(Prop $prop): MethodGenerator
    {
        $method = new MethodGenerator(
            'get' . ucfirst($prop->getName()),
            [],
            MethodGenerator::FLAG_PUBLIC,
            'return $this->' . $prop->getName() . ';'
        );

        $type = new Type($this->obj, $prop, $this->namespace);

        $method->setReturnType($type->php());

        if ($type->fullyExpressedInPhp()) {
            return $method;
        }

        $docBlocType = $type->docbloc();
        $psalmType = $type->psalm();

        $tags = [
            new ReturnTag($docBlocType)
        ];
        if ($docBlocType != $psalmType) {
            $tags[] = new GenericTag('psalm-return', $psalmType);
        }
        $method->setDocBlock(new DocBlockGenerator(null, null, $tags));

        return $method;
    }

    private function constructorGenerator(): MethodGenerator
    {
        $method = new MethodGenerator('__construct');

        $params = [];
        $tags = [];
        $body = [];

        $haveExtra = false;
        foreach ($this->obj->props()->values() as $prop) {
            if ($prop->isFieldOptional()) {
                $haveExtra = true;
                continue;
            }
            $type = new Type($this->obj, $prop, $this->namespace);

            $param = new ParameterGenerator($prop->getName(), $type->php());
            $params[] = $param;

            $docType = $type->docbloc();
            $tags[] = new ParamTag($param->getName(), [$docType]);
            $psalmType = $type->psalm();
            if ($psalmType != $docType) {
                $tags[] = new GenericTag('psalm-param', $psalmType . ' $' . $param->getName());
            }

            $body[] = '$this->' . $param->getName() . ' = $' . $param->getName() . ';';
        }

        if ($haveExtra) {
            $params[] = new ParameterGenerator('extra', 'array');
            $tags[] = new ParamTag('extra', ['array']);
            $tags[] = new GenericTag('psalm-param', $this->extraType() . ' $extra');
            $body[] = '$this->extra = $extra;';
        }

        $method->setParameters($params);
        $method->setDocBlock(new DocBlockGenerator(null, null, $tags));
        $method->setBody(join("\n", $body));
        return $method;
    }

    private function extraType(): string
    {
        $optionalFields = $this->obj->optionalProp();

        $o = new Obj('_');
        foreach ($optionalFields as $prop) {
            $o->addProp($prop);
        }
        $type = new ObjectLikeArray($o, $this->namespace, $this->parseResult, false);
        return $type->toString();
    }

    private function hasOptionalField(Prop $prop): MethodGenerator
    {
        $generator = new MethodGenerator('has' . ucfirst($prop->getName()));
        $generator->setReturnType('bool');
        $body = 'return array_key_exists(\'' . $prop->getName() . '\', $this->extra);';
        $generator->setBody($body);
        return $generator;
    }

    private function getOptionalField(Prop $prop): MethodGenerator
    {
        $generator = new MethodGenerator('get' . ucfirst($prop->getName()));
        $type = new Type($this->obj, $prop, $this->namespace, false);
        $generator->setReturnType($type->php());
        $body = 'if (array_key_exists(\'' . $prop->getName() . '\', $this->extra)) {' . "\n";
        $body .= '    return $this->extra[\'' . $prop->getName() . '\'];' . "\n";
        $body .= "}\n";
        $body .= 'throw new \RunTimeException(\'Field not set\');' . "\n";
        $generator->setBody($body);
        return $generator;
    }

    private function extraProperty(): PropertyWithTypeGenerator
    {
        $extra = new PropertyWithTypeGenerator(
            'extra',
            null,
            PropertyWithTypeGenerator::FLAG_PRIVATE
        );
        $extra->setDocBlock(
            new DocBlockGenerator(
                null,
                null,
                [
                    new GenericTag('psalm-var', $this->extraType())
                ]
            )
        );
        $extra->setType('array');
        return $extra;
    }
}

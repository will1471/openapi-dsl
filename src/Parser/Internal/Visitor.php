<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\Parser\Internal;

use Phplrt\Compiler\SampleNode;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Will1471\OpenApiDsl\DSL\Endpoint;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\EnumMember;
use Will1471\OpenApiDsl\DSL\Obj;

/**
 * @internal
 */
final class Visitor extends \Phplrt\Visitor\Visitor
{

    /**
     * @var Obj[]
     */
    public array $objs = [];

    /**
     * @var Enum[]
     */
    public array $enums = [];

    /**
     * @var Endpoint[]
     */
    public array $endpoints = [];

    private ?Obj $currentObj = null;
    private ?PropBuilder $propBuilder = null;
    private ?EndpointBuilder $endpointBuilder = null;
    private ?EnumBuilder $enumBuilder = null;

    public function enter(NodeInterface $node)
    {
        assert($node instanceof SampleNode);
        $enterMethod = 'enter' . ucfirst($node->getState());
        if (method_exists($this, $enterMethod)) {
            $this->$enterMethod($node);
        }
        return null;
    }

    public function leave(NodeInterface $node)
    {
        assert($node instanceof SampleNode);
        $exitMethod = 'exit' . ucfirst($node->getState());
        if (method_exists($this, $exitMethod)) {
            $this->$exitMethod($node);
        }
        return null;
    }

    private function enterObj(SampleNode $node): void
    {
        assert($node->children[0] instanceof TokenInterface);
        $name = $node->children[0]->getValue();
        $this->objs[] = $this->currentObj = new Obj($name);
    }

    private function enterFieldName(SampleNode $node): void
    {
        assert($node->children[0] instanceof TokenInterface);
        $this->propBuilder = new PropBuilder();
        $this->propBuilder->setFieldName($node->children[0]->getValue());
        $this->propBuilder->setFieldOptional(count($node->children) > 1);
    }

    private function enterFieldType(SampleNode $node): void
    {
        assert($this->propBuilder != null);
        foreach ($node->children as $child) {
            assert($child instanceof TokenInterface);
            $val = $child->getValue();
            if ($val == '?') {
                $this->propBuilder->setTypeNullable(true);
            } elseif ($val == '[]') {
                $this->propBuilder->setTypeIsList(true);
            } else {
                $this->propBuilder->setTypeName($val);
            }
        }
    }

    private function exitField(): void
    {
        assert($this->propBuilder != null);
        assert($this->currentObj != null);
        $this->currentObj->addProp($this->propBuilder->build());
    }

    private function enterEnum(SampleNode $node): void
    {
        assert($node->children[0] instanceof TokenInterface);
        $this->enumBuilder = new EnumBuilder($node->children[0]->getValue());
    }

    private function enterMember(SampleNode $node): void
    {
        assert($node->children[0] instanceof TokenInterface);
        assert($this->enumBuilder instanceof EnumBuilder);
        $this->enumBuilder->addMember(new EnumMember($node->children[0]->getValue()));
    }

    private function exitEnum(): void
    {
        assert($this->enumBuilder != null);
        $this->enums[] = $this->enumBuilder->build();
    }

    private function enterEndpoint(SampleNode $node): void
    {
        assert($node->children[0] instanceof TokenInterface);
        $this->endpointBuilder = new EndpointBuilder();
        $this->endpointBuilder->setMethod($node->children[0]->getValue());
    }

    private function enterPath(SampleNode $node): void
    {
        assert($this->endpointBuilder instanceof EndpointBuilder);
        $buffer = '';
        /** @var mixed $child */
        foreach ($node->children as $child) {
            assert($child instanceof TokenInterface);
            $buffer .= $child->getValue();
        }
        $this->endpointBuilder->setPath($buffer);
    }

    private function enterEndpointInput(SampleNode $node): void
    {
        assert($this->endpointBuilder instanceof EndpointBuilder);
        assert($node->children[0] instanceof TokenInterface);
        $this->endpointBuilder->setInputType($node->children[0]->getValue());
    }

    private function enterEndpointOutput(SampleNode $node): void
    {
        assert($this->endpointBuilder instanceof EndpointBuilder);
        assert($node->children[0] instanceof TokenInterface);
        $this->endpointBuilder->setOutputType($node->children[0]->getValue());
    }

    private function exitEndpoint(): void
    {
        assert($this->endpointBuilder != null);
        $this->endpoints[] = $this->endpointBuilder->build();
    }
}

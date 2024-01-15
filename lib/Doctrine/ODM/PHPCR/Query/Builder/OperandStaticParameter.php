<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandStaticParameter extends AbstractLeafNode
{
    private string $variableName;

    public function __construct(AbstractNode $parent, string $variableName)
    {
        $this->variableName = $variableName;
        parent::__construct($parent);
    }

    public function getVariableName(): string
    {
        return $this->variableName;
    }

    public function getNodeType(): string
    {
        return self::NT_OPERAND_STATIC;
    }
}

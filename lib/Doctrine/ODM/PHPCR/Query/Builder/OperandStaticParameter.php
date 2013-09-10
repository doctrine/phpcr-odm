<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandStaticParameter extends AbstractLeafNode
{
    protected $variableName;

    public function __construct(AbstractNode $parent, $variableName)
    {
        $this->variableName = $variableName;
        parent::__construct($parent);
    }

    public function getVariableName() 
    {
        return $this->variableName;
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_STATIC;
    }
}

<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandStaticLiteral extends AbstractLeafNode
{
    protected $value;

    public function __construct(AbstractNode $parent, $value)
    {
        $this->value = $value;
        parent::__construct($parent);
    }

    public function getValue() 
    {
        return $this->value;
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_STATIC;
    }
}

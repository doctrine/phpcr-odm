<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandDynamicLength extends AbstractLeafNode
{
    protected $selectorName;
    protected $propertyName;

    public function __construct(AbstractNode $parent, $selectorName, $propertyName)
    {
        $this->propertyName = $propertyName;
        $this->selectorName = $selectorName;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }

    public function getPropertyName() 
    {
        return $this->propertyName;
    }

    public function getSelectorName() 
    {
        return $this->selectorName;
    }
}

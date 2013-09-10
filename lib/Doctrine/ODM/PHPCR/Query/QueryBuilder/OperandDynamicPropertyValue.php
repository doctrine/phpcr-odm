<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandDynamicPropertyValue extends AbstractLeafNode
{
    protected $selectorName;
    protected $propertyName;

    public function __construct(AbstractNode $parent, $field)
    {
        list($selectorName, $propertyName) = $this->explodeField($field);
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }

    public function getSelectorName() 
    {
        return $this->selectorName;
    }

    public function getPropertyName() 
    {
        return $this->propertyName;
    }
}

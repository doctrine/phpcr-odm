<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandDynamicLength extends AbstractLeafNode
{
    protected $selectorName;
    protected $propertyName;

    Public function __construct(AbstractNode $parent, $field)
    {
        list($selectorName, $propertyName) = $this->explodeField($field);
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

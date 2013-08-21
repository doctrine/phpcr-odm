<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandDynamicPropertyValue extends AbstractLeafNode implements OperandDynamicInterface
{
    protected $selectorName;
    protected $propertyName;

    public function __construct(AbstractNode $parent, $selectorName, $propertyName)
    {
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
        parent::__construct($parent);
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

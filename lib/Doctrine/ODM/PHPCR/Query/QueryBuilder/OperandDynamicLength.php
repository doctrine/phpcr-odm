<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandDynamicLength extends AbstractLeafNode implements OperandDynamicInterface
{
    // todo: how should this work ??
    protected $propertyValue;

    public function __construct(AbstractNode $parent, PropertyValueInterface $propertyValue)
    {
        $this->propertyValue = $propertyValue;
        parent::__construct($parent);
    }

    public function getPropertyValue() 
    {
        return $this->propertyValue;
    }
}

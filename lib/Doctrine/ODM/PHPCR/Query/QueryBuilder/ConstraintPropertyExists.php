<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

class ConstraintPropertyExists extends AbstractLeafNode
{
    protected $propertyName;
    protected $selectorName;

    public function __construct(AbstractNode $parent, $selectorName, $propertyName)
    {
        $this->propertyName = $propertyName;
        $this->selectorName = $selectorName;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
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

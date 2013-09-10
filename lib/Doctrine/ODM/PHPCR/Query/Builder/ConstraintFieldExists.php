<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

class ConstraintFieldExists extends AbstractLeafNode
{
    protected $propertyName;
    protected $selectorName;

    public function __construct(AbstractNode $parent, $field)
    {
        list($selectorName, $propertyName) = $this->explodeField($field);
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

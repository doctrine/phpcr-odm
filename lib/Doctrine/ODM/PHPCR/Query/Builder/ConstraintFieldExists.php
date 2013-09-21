<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

class ConstraintFieldExists extends AbstractLeafNode
{
    protected $field;
    protected $alias;

    public function __construct(AbstractNode $parent, $field)
    {
        list($alias, $field) = $this->explodeField($field);
        $this->field = $field;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getField() 
    {
        return $this->field;
    }

    public function getAlias() 
    {
        return $this->alias;
    }
}

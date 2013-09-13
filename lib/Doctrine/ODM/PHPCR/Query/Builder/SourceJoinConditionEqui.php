<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class SourceJoinConditionEqui extends AbstractLeafNode
{
    protected $property1;
    protected $selector1;
    protected $property2;
    protected $selector2;

    public function __construct($parent, $field1, $field2)
    {
        list($selector1, $property1) = $this->explodeField($field1);
        list($selector2, $property2) = $this->explodeField($field2);
        parent::__construct($parent);
        $this->property1 = $property1;
        $this->selector1 = $selector1;
        $this->property2 = $property2;
        $this->selector2 = $selector2;
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getProperty1() 
    {
        return $this->property1;
    }

    public function getSelector1() 
    {
        return $this->selector1;
    }

    public function getProperty2() 
    {
        return $this->property2;
    }

    public function getSelector2() 
    {
        return $this->selector2;
    }
}


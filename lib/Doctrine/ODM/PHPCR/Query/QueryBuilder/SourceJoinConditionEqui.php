<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class SourceJoinConditionEqui extends AbstractLeafNode implements SourceInterface
{
    protected $property1;
    protected $selector1;
    protected $property2;
    protected $selector2;

    public function __construct($parent, $property1, $selector1, $property2, $selector2)
    {
        parent::__construct($parent);
        $this->property1 = $property1;
        $this->selector1 = $selector1;
        $this->property2 = $property2;
        $this->selector2 = $selector2;
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


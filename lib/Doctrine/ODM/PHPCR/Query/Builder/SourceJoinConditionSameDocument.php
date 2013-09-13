<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

class SourceJoinConditionSameDocument extends AbstractLeafNode
{
    protected $selector1Name;
    protected $selector2Name;
    protected $selector2Path;

    public function __construct($parent, $selector1Name, $selector2Name, $selector2Path)
    {
        $this->selector1Name = $selector1Name;
        $this->selector2Name = $selector2Name;
        $this->selector2Path = $selector2Path;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getSelector1Name() 
    {
        return $this->selector1Name;
    }

    public function getSelector2Name() 
    {
        return $this->selector2Name;
    }

    public function getSelector2Path() 
    {
        return $this->selector2Path;
    }
    
}

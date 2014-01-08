<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

class SourceJoinConditionSameDocument extends AbstractLeafNode
{
    protected $alias1Name;
    protected $alias2Name;
    protected $alias2Path;

    public function __construct($parent, $alias1Name, $alias2Name, $alias2Path)
    {
        $this->alias1Name = $alias1Name;
        $this->alias2Name = $alias2Name;
        $this->alias2Path = $alias2Path;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getAlias1Name() 
    {
        return $this->alias1Name;
    }

    public function getAlias2Name() 
    {
        return $this->alias2Name;
    }

    public function getAlias2Path() 
    {
        return $this->alias2Path;
    }
    
}

<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class SourceJoinConditionChildDocument extends AbstractLeafNode
{
    protected $childAlias;
    protected $parentAlias;

    public function __construct($parent, $childAlias, $parentAlias)
    {
        $this->childAlias = (string) $childAlias;
        $this->parentAlias = (string) $parentAlias;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getChildAlias() 
    {
        return $this->childAlias;
    }

    public function getParentAlias() 
    {
        return $this->parentAlias;
    }
}

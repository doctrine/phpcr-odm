<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class SourceJoinConditionChildDocument extends AbstractLeafNode implements 
    SourceJoinConditionInterface
{
    protected $childSelectorName;
    protected $parentSelectorName;

    public function __construct($parent, $childSelectorName, $parentSelectorName)
    {
        $this->childSelectorNode = (string) $childSelectorName;
        $this->parentSelectorName = (string) $parentSelectorName;
        parent::__construct($parent);
    }

    public function getChildSelectorName() 
    {
        return $this->childSelectorName;
    }

    public function getParentSelectorName() 
    {
        return $this->parentSelectorName;
    }
}

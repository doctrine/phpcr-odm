<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class ConstraintChild extends AbstractLeafNode
{
    public function __construct(AbstractNode $parent, $selectorName, $parentPath)
    {
        $this->selectorName = $selectorName;
        $this->parentPath = $parentPath;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getParentPath() 
    {
        return $this->parentPath;
    }

    public function getSelectorName() 
    {
        return $this->selectorName;
    }
}

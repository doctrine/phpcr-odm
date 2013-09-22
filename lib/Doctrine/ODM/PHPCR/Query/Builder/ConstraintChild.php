<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class ConstraintChild extends AbstractLeafNode
{
    public function __construct(AbstractNode $parent, $alias, $parentPath)
    {
        $this->alias = $alias;
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

    public function getAlias() 
    {
        return $this->alias;
    }
}

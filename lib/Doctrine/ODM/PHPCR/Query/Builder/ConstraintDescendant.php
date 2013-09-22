<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class ConstraintDescendant extends AbstractLeafNode
{
    protected $alias;
    protected $ancestorPath;

    public function __construct(AbstractNode $parent, $alias, $ancestorPath)
    {
        $this->alias = $alias;
        $this->ancestorPath = $ancestorPath;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getAncestorPath() 
    {
        return $this->ancestorPath;
    }

    public function getAlias() 
    {
        return $this->alias;
    }
}

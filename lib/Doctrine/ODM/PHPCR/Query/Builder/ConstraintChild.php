<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;


/**
 * Constraint evaluates to true if aliased node is a child of
 * the given parent path for the given alias.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
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

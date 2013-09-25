<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint which evaluates to true when the aliased document 
 * path is a decscendant of the specified ancestor path.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
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

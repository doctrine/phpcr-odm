<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

/**
 * Constraint which evaluates to true if the aliased document is
 * reachable by the specified path.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintSame extends AbstractLeafNode
{
    protected $alias;
    protected $path;

    public function __construct(AbstractNode $parent, $alias, $path)
    {
        $this->alias = $alias;
        $this->path = $path;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getPath()
    {
        return $this->path;
    }
}
